"""
API FastAPI: porting di AuthController e RestaurantController (Laravel/Filament).

- Stesso DB MySQL di Laravel (DATABASE_URL), stesse tabelle/colonne.
- Login/registrazione compatibili con Laravel: password bcrypt ($2y$) e token
  Sanctum nella tabella personal_access_tokens, quindi i token emessi da qui
  valgono anche su Laravel e viceversa.
- Le rotte sono dichiarate senza /api (root_path="/api", nginx toglie il prefisso
  se punta a questo servizio).
"""

import hashlib
import hmac
import json
import os
import secrets
import string
import zlib
from datetime import date, datetime, timezone
from decimal import Decimal

import bcrypt
import stripe
from fastapi import Depends, FastAPI, HTTPException, Request
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse
from pydantic import BaseModel, EmailStr, Field
from sqlalchemy import create_engine, text

DATABASE_URL = os.environ.get(
    "DATABASE_URL", "mysql+pymysql://fooddelivery:fooddelivery@db:3306/filament"
)
STRIPE_SECRET_KEY = os.environ.get("STRIPE_SECRET_KEY", "")
FRONTEND_URL = os.environ.get("FRONTEND_URL", "http://localhost").rstrip("/")

USER_MODEL = "App\\Models\\User"  # tokenable_type usato da Sanctum

engine = create_engine(DATABASE_URL, pool_pre_ping=True)
app = FastAPI(root_path="/api")


# ---------------------------------------------------------------------------
# Utility
# ---------------------------------------------------------------------------

def now() -> datetime:
    return datetime.now(timezone.utc).replace(tzinfo=None)


def serialize_row(row) -> dict:
    """Converte una riga DB nel formato JSON prodotto da Eloquent toArray()."""
    out = {}
    for key, value in dict(row._mapping).items():
        if isinstance(value, datetime):
            value = value.strftime("%Y-%m-%dT%H:%M:%S.%fZ")
        elif isinstance(value, date):
            value = value.isoformat()
        elif isinstance(value, Decimal):
            value = str(value)  # Eloquent restituisce i decimal come stringa ("7.00")
        out[key] = value
    return out


def hash_password(password: str) -> str:
    # Laravel usa il prefisso $2y$; bcrypt Python genera $2b$ (algoritmo identico)
    hashed = bcrypt.hashpw(password.encode(), bcrypt.gensalt(rounds=12)).decode()
    return "$2y$" + hashed[4:]


def check_password(password: str, hashed: str) -> bool:
    if hashed.startswith("$2y$"):
        hashed = "$2b$" + hashed[4:]
    try:
        return bcrypt.checkpw(password.encode(), hashed.encode())
    except ValueError:
        return False


def create_token(conn, user_id: int, name: str = "auth_token") -> str:
    """Equivalente di $user->createToken($name)->plainTextToken (Sanctum)."""
    alphabet = string.ascii_letters + string.digits
    entropy = "".join(secrets.choice(alphabet) for _ in range(40))
    plain = entropy + format(zlib.crc32(entropy.encode()) & 0xFFFFFFFF, "08x")
    ts = now()
    result = conn.execute(
        text(
            "INSERT INTO personal_access_tokens "
            "(tokenable_type, tokenable_id, name, token, abilities, created_at, updated_at) "
            "VALUES (:type, :uid, :name, :token, :abilities, :ts, :ts)"
        ),
        {
            "type": USER_MODEL,
            "uid": user_id,
            "name": name,
            "token": hashlib.sha256(plain.encode()).hexdigest(),
            "abilities": '["*"]',
            "ts": ts,
        },
    )
    return f"{result.lastrowid}|{plain}"


# ---------------------------------------------------------------------------
# Errori nel formato Laravel
# ---------------------------------------------------------------------------

@app.exception_handler(RequestValidationError)
async def validation_error(request: Request, exc: RequestValidationError):
    errors: dict[str, list[str]] = {}
    for err in exc.errors():
        field = str(err["loc"][-1]) if err["loc"] else "body"
        errors.setdefault(field, []).append(err["msg"])
    first = next(iter(errors.values()))[0] if errors else "The given data was invalid."
    return JSONResponse({"message": first, "errors": errors}, status_code=422)


# ---------------------------------------------------------------------------
# Auth (equivalente del middleware auth:sanctum)
# ---------------------------------------------------------------------------

def current_user(request: Request) -> dict:
    unauthenticated = HTTPException(status_code=401, detail="Unauthenticated.")
    header = request.headers.get("Authorization", "")
    if not header.lower().startswith("bearer "):
        raise unauthenticated
    token = header[7:].strip()
    if "|" not in token:
        raise unauthenticated
    token_id, plain = token.split("|", 1)
    if not token_id.isdigit():
        raise unauthenticated

    with engine.begin() as conn:
        row = conn.execute(
            text(
                "SELECT id, tokenable_type, tokenable_id, token, expires_at "
                "FROM personal_access_tokens WHERE id = :id"
            ),
            {"id": int(token_id)},
        ).first()
        if (
            row is None
            or row.tokenable_type != USER_MODEL
            or not hmac.compare_digest(row.token, hashlib.sha256(plain.encode()).hexdigest())
            or (row.expires_at is not None and row.expires_at <= now())
        ):
            raise unauthenticated

        user = conn.execute(
            text("SELECT id, name, lastname, email FROM users WHERE id = :id"),
            {"id": row.tokenable_id},
        ).first()
        if user is None:
            raise unauthenticated

        conn.execute(
            text("UPDATE personal_access_tokens SET last_used_at = :ts WHERE id = :id"),
            {"ts": now(), "id": row.id},
        )
    return dict(user._mapping)


@app.exception_handler(HTTPException)
async def http_error(request: Request, exc: HTTPException):
    return JSONResponse({"message": exc.detail}, status_code=exc.status_code)


# ---------------------------------------------------------------------------
# AuthController
# ---------------------------------------------------------------------------

class RegisterBody(BaseModel):
    username: EmailStr
    password: str = Field(min_length=1)
    nome: str = Field(min_length=1, max_length=255)


class LoginBody(BaseModel):
    username: EmailStr
    password: str = Field(min_length=1)


@app.post("/user/register")
def register(body: RegisterBody):
    with engine.begin() as conn:
        exists = conn.execute(
            text("SELECT 1 FROM users WHERE email = :email"), {"email": body.username}
        ).first()
        if exists:
            return JSONResponse({"ko": "user already exists"}, status_code=409)

        ts = now()
        conn.execute(
            text(
                "INSERT INTO users (name, email, password, created_at, updated_at) "
                "VALUES (:name, :email, :password, :ts, :ts)"
            ),
            {
                "name": body.nome,
                "email": body.username,
                "password": hash_password(body.password),
                "ts": ts,
            },
        )
    return JSONResponse({"ok": "user created"}, status_code=201)


@app.post("/user/login")
def login(body: LoginBody):
    with engine.begin() as conn:
        user = conn.execute(
            text("SELECT id, password FROM users WHERE email = :email"),
            {"email": body.username},
        ).first()
        if user is None or not check_password(body.password, user.password):
            return JSONResponse({"ko": "user not found"}, status_code=401)

        token = create_token(conn, user.id)
    return {"ok": "user found", "token": token}


@app.get("/user")
def me(user: dict = Depends(current_user)):
    return user


# ---------------------------------------------------------------------------
# RestaurantController
# ---------------------------------------------------------------------------

class CartItem(BaseModel):
    price_id: str | None = None
    quantity: int = Field(ge=1)

    model_config = {"extra": "allow"}  # il carrello contiene anche gli altri campi del piatto


class PlaceOrderBody(BaseModel):
    cart: list[CartItem] = Field(min_length=1)
    restaurant_id: int


@app.get("/restaurants/list")
def restaurant_list(user: dict = Depends(current_user)):
    with engine.connect() as conn:
        rows = conn.execute(text("SELECT * FROM restaurants")).all()
    return {"restaurants": [serialize_row(r) for r in rows]}


@app.get("/restaurants/{id}/menu")
def get_menu(id: int, user: dict = Depends(current_user)):
    with engine.connect() as conn:
        restaurant = conn.execute(
            text("SELECT id FROM restaurants WHERE id = :id"), {"id": id}
        ).first()
        if restaurant is None:
            raise HTTPException(status_code=404, detail="No query results for model [App\\Models\\Restaurant] " + str(id))

        dishes = conn.execute(
            text(
                "SELECT d.*, rd.restaurant_id AS pivot_restaurant_id, rd.dishes_id AS pivot_dishes_id "
                "FROM dish d JOIN restaurant_dishes rd ON rd.dishes_id = d.id "
                "WHERE rd.restaurant_id = :id"
            ),
            {"id": id},
        ).all()
        categories = {
            c.id: c.dish_category
            for c in conn.execute(text("SELECT id, dish_category FROM dish_categories")).all()
        }

    menu: dict[int, dict] = {}
    for row in dishes:
        dish = serialize_row(row)
        dish["pivot"] = {
            "restaurant_id": dish.pop("pivot_restaurant_id"),
            "dishes_id": dish.pop("pivot_dishes_id"),
        }
        cat_id = dish["category_id"]
        if cat_id not in menu:
            menu[cat_id] = {"id": cat_id, "category": categories.get(cat_id), "dishes": []}
        menu[cat_id]["dishes"].append(dish)

    return {"menu": list(menu.values())}


@app.post("/order/place")
def place_order(body: PlaceOrderBody, user: dict = Depends(current_user)):
    line_items = [{"price": item.price_id, "quantity": item.quantity} for item in body.cart]

    ts = now()
    with engine.begin() as conn:
        conn.execute(
            text(
                "INSERT INTO orders (user_id, restaurant_id, `order`, status, created_at, updated_at) "
                "VALUES (:uid, :rid, :order, 'placed', :ts, :ts)"
            ),
            {
                "uid": user["id"],
                "rid": body.restaurant_id,
                "order": json.dumps(line_items),
                "ts": ts,
            },
        )

    try:
        session = stripe.checkout.Session.create(
            api_key=STRIPE_SECRET_KEY,
            success_url=f"{FRONTEND_URL}/payments/ok",
            line_items=line_items,
            mode="payment",
        )
    except stripe.StripeError as e:
        return JSONResponse(
            {"message": getattr(e, "user_message", None) or str(e), "exception": type(e).__name__},
            status_code=500,
        )
    return {"url": session.url}
