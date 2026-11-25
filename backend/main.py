import os
from fastapi import FastAPI, Depends, HTTPException
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from pydantic import BaseModel, Field
from sqlalchemy import create_engine, text
from sqlalchemy.orm import Session, sessionmaker
import bcrypt
import hashlib
import secrets
import string
import zlib
from datetime import datetime, timezone
import hmac
from stripe import StripeClient
import json

# 1. Recupera la stringa di connessione dalle variabili d'ambiente (definite nel docker-compose.yml)
# Nota: usa 'db' come host se sei all'interno dei container Docker
DATABASE_URL = os.getenv("DATABASE_URL")

# 2. Configura l'engine e la sessione di SQLAlchemy
engine = create_engine(DATABASE_URL)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)

# 3. Dipendenza (Dependency Injection) per aprire e chiudere la sessione del DB per ogni richiesta
def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

app = FastAPI()

class Login(BaseModel):
    username: str 
    password: str

class Register(BaseModel):
    username: str = Field(min_length=1)
    password: str = Field(min_length=1)
    name: str = Field(min_length=1)
    lastname: str = Field(min_length=1)

bearer = HTTPBearer(auto_error=False)

def check_token(cred: HTTPAuthorizationCredentials | None = Depends(bearer), db: Session = Depends(get_db)):
    if cred is None:
        raise HTTPException(status_code=401, detail="Unauthenticated.")
    token_id, sep, plain = cred.credentials.partition("|")         # già senza "Bearer "
    
    check = db.execute(
        text(
            "select tokenable_id, token from personal_access_tokens where id = :id"
        ),
        {
            'id': token_id
        }
    ).first()
    if check is None or not hmac.compare_digest(check.token, hashlib.sha256(plain.encode()).hexdigest()):
        raise HTTPException(status_code=401, detail="Unauthenticated.")
    return check.tokenable_id

@app.post('/user/register')
def register(item: Register, db: Session = Depends(get_db)):
    result = db.execute(text("select id from users where email = :email"), {"email": item.username}).first()
    if result is not None:
        raise HTTPException(status_code=409, detail="Utente già registrato")
    hashed = bcrypt.hashpw(item.password.encode(), bcrypt.gensalt(rounds=12)).decode()
    hashed = hashed.replace("$2b$", "$2y$", 1)   # formato Laravel
    now = datetime.now(timezone.utc).replace(tzinfo=None)

    insert = db.execute(
        text(
            "INSERT INTO users(email, password, name, lastname, created_at, updated_at) values (:email, :password, :name, :lastname, :created_at, :updated_at)"
        ),
        {
            'email': item.username,
            'password': hashed,
            'name': item.name,
            'lastname': item.lastname,
            'created_at': now,
            'updated_at': now
        }
    )
    db.commit()

    return {'ok': 'Utente registrato correttamente'}


@app.post('/user/login')
def login(item: Login, db: Session = Depends(get_db)):
    result = db.execute(text("select id, password from users where email = :email"), {"email": item.username}).first()
    if not result:
        raise HTTPException(status_code=401, detail="Unauthorized")
    hashed = result.password.replace("$2y$", "$2b$", 1)
    if not bcrypt.checkpw(item.password.encode(), hashed.encode()):
        raise HTTPException(status_code=401, detail="Unauthorized")

    # Token come Sanctum: 40 caratteri casuali + CRC32 esadecimale; nel DB solo lo SHA-256
    alphabet = string.ascii_letters + string.digits
    entropy = "".join(secrets.choice(alphabet) for _ in range(40))
    plain = entropy + format(zlib.crc32(entropy.encode()) & 0xFFFFFFFF, "08x")
    now = datetime.now(timezone.utc).replace(tzinfo=None)

    insert = db.execute(
        text(
            "INSERT INTO personal_access_tokens "
            "(tokenable_type, tokenable_id, name, token, abilities, created_at, updated_at) "
            "VALUES (:type, :uid, :name, :token, :abilities, :ts, :ts)"
        ),
        {
            "type": "App\\Models\\User",
            "uid": result.id,
            "name": "auth_token",
            "token": hashlib.sha256(plain.encode()).hexdigest(),
            "abilities": '["*"]',
            "ts": now,
        },
    )
    db.commit()

    return {"token": f"{insert.lastrowid}|{plain}"}

@app.get('/restaurants/list', dependencies=[Depends(check_token)])
def restaurants_list(db: Session = Depends(get_db)):
    restaurant = db.execute(
        text(
            "select id, type, name, address from restaurants"
        )
    )

    lista = []
    for r in restaurant:
        lista.append({
            'id': r.id
            ,'type': r.type
            ,'name': r.name
            ,'address': r.address
        })

    return {"restaurants": lista}

@app.get('/restaurants/{id}/menu', dependencies=[Depends(check_token)])
def restaurant_menu(id: int, db: Session = Depends(get_db)):
    restaurant = db.execute(
        text(
            "select name, type, address, city, CAP from restaurants where id = :id"
        )
        ,{
            'id': id
        }
    ).mappings().first()
    if not restaurant:
        raise HTTPException(status_code=404, detail="Restaurant not found")

    dishes = db.execute(
        text(
            "select dish.category_id, dish.id, dish.name, dish.price, dish.price_id, dish_categories.dish_category " \
            "from dish " \
            "join dish_categories " \
            "   on dish.category_id = dish_categories.id " \
            "join restaurant_dishes " \
            "   on dish.id = restaurant_dishes.dishes_id " \
            "where restaurant_dishes.restaurant_id = :id"
        )
        ,{
            'id': id
        }
    )

    lista = {}
    for d in dishes:
        if d.category_id not in lista:
            lista[d.category_id] = {
                'id': d.category_id
                ,'category': d.dish_category
                ,'dishes': []
            }
        lista[d.category_id]['dishes'].append({
            'id': d.id
            ,'name': d.name
            ,'price': d.price
            ,'price_id': d.price_id
        })

    return {"menu": list(lista.values()), "restaurant": restaurant}

class CartItem(BaseModel):
    price_id: str
    quantity: int = Field(ge=1)

class PlaceOrder(BaseModel):
    cart: list[CartItem] = Field(min_length=1)
    restaurant_id: int

@app.post('/order/place')
def place_order(item: PlaceOrder, user_id = Depends(check_token), db: Session = Depends(get_db)):
    line_items = []
    for k in item.cart:
        line_items.append({
            "price": k.price_id
            ,"quantity": k.quantity
        })
    now = datetime.now(timezone.utc).replace(tzinfo=None)

    insert = db.execute(
        text(
            "insert into orders(user_id, restaurant_id, `order`, status, created_at, updated_at) values "
            "(:user_id, :restaurant_id, :order, 'placed', :ts, :ts)"
        )
        ,{
            'user_id': user_id
            ,'restaurant_id': item.restaurant_id
            ,'order': json.dumps(line_items)
            ,'ts': now
        }
    )
    db.commit()

    stripe_key = os.getenv('STRIPE_SECRET_KEY')
    client = StripeClient(stripe_key)    
    session = client.v1.checkout.sessions.create({
        "success_url": os.getenv('STRIPE_SUCCESS_URL'),
        "cancel_url": os.getenv('STRIPE_CANCEL_URL'),
        "line_items": line_items,
        "mode": "payment"
    })
    return {"url": session.url}