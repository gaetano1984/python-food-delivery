# backend/CLAUDE.md

API FastAPI usata dal frontend. È un porting delle API Laravel (`filament/app/Http/Controllers/`): **le risposte devono avere la stessa forma JSON di quelle Laravel**, perché il frontend è stato scritto per quelle.

## Struttura

- `main.py`: l'unico file caricato (`uvicorn main:app --reload`).
- `__main.py`: porting completo di riferimento, **non eseguito**. Contiene implementazioni già pronte da cui copiare.
- Le rotte si dichiarano **senza** `/api`: nginx toglie il prefisso. `FastAPI()` va creato senza `root_path`.

## API (contratti)

| Metodo e path | Auth | Body | Risposta 200 |
|---|---|---|---|
| `POST /user/register` | no | `{"username": email, "password", "name", "lastname"}` | `{"ok": "Utente registrato correttamente"}`; 409 se l'email esiste |
| `POST /user/login` | no | `{"username": email, "password"}` | `{"token": "id\|plain"}` |
| `GET /restaurants/list` | Bearer | nessuno | `{"restaurants": [{id, type, name, address}]}` |
| `GET /restaurants/{id}/menu` | Bearer | nessuno | `{"restaurant": {name, type, address, city, CAP}, "menu": [{id, category, dishes: [{id, name, price, price_id}]}]}`: `menu` è un **array**, non un oggetto |
| `POST /order/place` | Bearer | `{"cart": [{price_id, quantity, ...}], "restaurant_id"}` | `{"url": "<stripe checkout>"}` |

Errori:
- `POST /user/login`: 401 se le credenziali sono errate (stessa risposta per email inesistente e password sbagliata).
- Rotte protette: 401 `{"detail": "Unauthenticated."}` se il token manca o non è valido.
- `GET /restaurants/{id}/menu`: 404 se il ristorante non esiste.
- 422 se il body non è valido (lo fa FastAPI da solo).

`price_id` nei piatti del menu è obbligatorio: il carrello lo copia dal piatto e `/order/place` lo manda a Stripe.

## Regole

- **Endpoint con `def`, non `async def`**: la sessione SQLAlchemy è sincrona e dentro `async def` bloccherebbe il server.
- **DB**: usa la dipendenza `db: Session = Depends(get_db)`. Le query si scrivono SQL con `text()` e **parametri con nome** (`:id` più un dict, mai f-string).
  - Per leggere i risultati: `.mappings().all()` per una lista di dict, `.first()` per una riga sola.
  - Dopo INSERT/UPDATE serve `db.commit()` (la sessione ha `autocommit=False`).
  - Non usare `select *` nelle JOIN: più tabelle hanno `id`/`created_at`, quindi elenca le colonne.
- **Auth**: `check_token` è una dipendenza, l'equivalente di `auth:sanctum`.
  - Se ti serve solo bloccare la rotta: `@app.get(..., dependencies=[Depends(check_token)])`.
  - Se ti serve l'utente: `user_id: int = Depends(check_token)` come parametro.
  - Per bloccare la richiesta deve **sollevare** `HTTPException(401)`. Il valore restituito non blocca niente.
- **Body**: un modello Pydantic per ogni body. Le liste annidate richiedono un modello per l'elemento (es. `CartItem` dentro `PlaceOrder.cart`). Usa `Field(ge=1)` e `Field(min_length=1)` per le validazioni.
- **Formato come Laravel**: i `decimal` vanno restituiti come stringa (`str(d.price)` → `"7.00"`). Dove Laravel usa `array_values`, restituisci `list(d.values())`.
- **Configurazione** solo da env (`DATABASE_URL`, `STRIPE_SECRET_KEY`, `STRIPE_SUCCESS_URL`, `STRIPE_CANCEL_URL`, `FRONTEND_URL`), passate da `docker-compose.yaml`.
- Non nominare variabili come i tipi predefiniti (`list`, `id`, `dict`), che li nascondono.

## Errori ricorrenti

- Un percorso senza `/` iniziale (`@app.get('health')`) dà 404.
- `FastApi` va scritto `FastAPI`.
- Una stringa SQL senza `text()` dà `ArgumentError`. Il metodo è `fetchall()`, non `fetchAll()`.
- Stringhe SQL spezzate su più righe: ogni pezzo deve finire con uno spazio.
- `"App\Models\User"` in Python va scritto `"App\\Models\\User"` (`\U` è un escape).
- Il parametro di path (`{id}`) deve stare nella firma della funzione, altrimenti `id` diventa la funzione predefinita di Python.

## Debug

- Se un'API non risponde, guarda prima `docker compose logs --tail 30 api`: di solito è un errore Python che impedisce all'app di partire.
- Per i test diretti: `http://127.0.0.1:8000/docs` (Swagger).
- Per creare un token e chiamare una rotta protetta, fai il login con `curl -X POST 127.0.0.1:8000/user/login -H "Content-Type: application/json" -d '{"username":"...","password":"..."}'`, poi usa `-H "Authorization: Bearer <token>"`.
