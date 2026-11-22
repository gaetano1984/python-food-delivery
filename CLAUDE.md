# CLAUDE.md

App di food delivery: il cliente fa login, sceglie un ristorante, compone il carrello e paga con Stripe Checkout.
Codice, testi UI e commenti sono in italiano.

## Architettura

| Cartella | Stack | Ruolo |
|---|---|---|
| `frontend/` | React 18, Vite 6, Tailwind 4, react-router 7, zod, axios | SPA per il cliente (spec in `frontend/CLAUDE.md`) |
| `backend/` | FastAPI, SQLAlchemy 2 (`text()`), pymysql, bcrypt, stripe; gestito con `uv` | **API usata dal frontend** (regole in `backend/CLAUDE.md`) |
| `filament/` | Laravel 13, Filament 5, Sanctum | Pannello admin e **proprietario dello schema DB** |
| `nginx/` | `default.conf` | Reverse proxy sulla porta 80 |

Tutto gira con Docker Compose e condivide un MySQL (`db`), database **`filament`**.

## Instradamento

| URL | Destinazione |
|---|---|
| `http://localhost/` | nginx → Vite (`frontend:5173`, HMR) |
| `http://localhost/api/...` | nginx → FastAPI (`api:8000`). `proxy_pass` ha lo **slash finale**, quindi `/api` viene tolto: `/api/user/login` arriva a FastAPI come `/user/login` |
| `http://localhost:8000` | FastAPI diretto (Postman, `/docs`) |
| `http://localhost:8001` | Laravel/Filament diretto (pannello admin) |

- Il frontend va aperto da `http://localhost/`, **mai da `:5173`**: da lì `/api` arriva a Vite, che risponde con `index.html`.
- In `nginx/default.conf` il blocco `/api/` verso Laravel è commentato. Per tornare a Laravel: `proxy_pass http://filament:8001;` **senza** slash finale, perché Laravel si aspetta il prefisso `/api`.
- Per testare da Postman su Windows usa `127.0.0.1` invece di `localhost`: `localhost` passa dal relay WSL e può restituire risposte vecchie dopo un reload.

## Comandi

```sh
docker compose up -d --build                    # tutto
docker compose logs -f api                      # primo posto dove guardare se un'API non risponde
docker compose restart frontend                 # dopo aver aggiunto file in frontend/public/
docker compose up -d api                        # dopo aver cambiato env nel compose (restart non le rilegge)
docker compose exec nginx nginx -t && docker compose exec nginx nginx -s reload
docker compose exec filament php artisan migrate
```

- I sorgenti sono montati in bind-mount: uvicorn `--reload` e Vite HMR vedono le modifiche da soli.
- Ricostruisci un'immagine (`--build`) solo se cambiano le dipendenze (`pyproject.toml`/`uv.lock`, `package.json`, `composer.json`).
- Le dipendenze Python si aggiungono con `uv add <pkg>` in `backend/`, mai nel `Dockerfile`.
- Credenziali MySQL: `fooddelivery`/`fooddelivery` su `localhost:3306`.

## Autenticazione

I token sono **compatibili con Sanctum**: FastAPI li scrive e li legge in `personal_access_tokens` nello stesso formato di Laravel. Un token emesso da uno dei due backend vale anche sull'altro.

- Al client si restituisce `"{id}|{plain}"`. `plain` è formato da 40 caratteri alfanumerici casuali più il CRC32 esadecimale (8 caratteri).
- Nel DB si salva solo `sha256(plain)`, con `tokenable_type = 'App\Models\User'`.
- Le password degli utenti sono bcrypt `$2y$` (Laravel). Per `bcrypt` Python vanno convertite in `$2b$` prima di `checkpw`.

## Schema DB

Lo schema è definito **solo** dalle migration Laravel in `filament/database/migrations/`. FastAPI non crea né modifica tabelle: le modifiche allo schema si fanno con una nuova migration Laravel.

I nomi sono irregolari, quindi controlla la migration prima di scrivere una query:
- `dish` è al singolare (non `dishes`);
- `dish_categories.dish_category` contiene il nome della categoria;
- la pivot `restaurant_dishes(restaurant_id, dishes_id)`;
- `restaurants.CAP` è maiuscolo, e `ingredients.is_removerable` è scritto così;
- le chiavi esterne sono `int` senza vincoli: un JOIN può perdere righe orfane;
- `orders.order` è un JSON con le righe Stripe e `status` vale `placed|paid|delivered|error`. `order` è una parola riservata, quindi va scritta tra backtick nelle query.
- I piatti hanno `stripe_id`/`price_id` (Stripe); `price` è `decimal(8,2)`.

## Filament (pannello admin)

- Le risorse seguono il layout di Filament 5: `app/Filament/Resources/<Plural>/{<Name>Resource.php, Pages/, Schemas/<Name>Form.php, Tables/<Plural>Table.php}`.
- Usa le API v5: `form(Schema $schema)` con `->components()`; i layout da `Filament\Schemas\Components`; le azioni da `Filament\Actions\*`; le statiche tipizzate (`string|BackedEnum|null $navigationIcon`). Un tipo sbagliato fa crollare tutto il pannello.
- Dopo aver spostato o rinominato classi: `docker compose exec filament composer dump-autoload`.
- Per il resto segui `filament/CLAUDE.md` (Laravel Boost).

## Problemi noti

- `backend/pyproject.toml` richiede Python `>=3.14`, mentre il `Dockerfile` parte da `python:3.12-slim`. Funziona perché `uv` scarica Python da solo.
- Il frontend non ha una rotta `/`.
- `backend/__main.py` è un porting di riferimento **non eseguito** (uvicorn carica solo `main.py`).
