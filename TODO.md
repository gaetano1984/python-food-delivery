# TODO

## Backend (FastAPI)
- [x] `Field` non importato in main.py: l'API non parte
- [x] `check_token`: verificare davvero il token su personal_access_tokens
- [x] `check_token`: `return check.tokenable_id` (serve a place_order per orders.user_id)
- [x] Login: credenziali errate → 401 (oggi 200 con {"ko"})
- [x] `/menu`: aggiungere `price_id` ai piatti (serve per l'ordine)
- [x] `/menu`: restituire anche `restaurant` (nome, tipo, indirizzo, città, CAP)
- [x] `/menu`: 404 se il ristorante non esiste
- [x] `place_order`: chiamata Stripe e redirect funzionanti
- [x] `place_order`: manca `db.commit()` dopo l'insert, gli ordini non vengono salvati in `orders`
- [x] `place_order`: chiave Stripe spostata in `.env` (radice) come `STRIPE_SECRET_KEY`
- [ ] Rigenerare la chiave Stripe (quella vecchia è stata nel codice e in chat)
- [x] `.env` nel `.gitignore` al momento del `git init`
- [x] `place_order`: togliere `dependencies=[Depends(check_token)]` dal decoratore (`check_token` gira due volte)
- [x] `place_order`: `success_url`/`cancel_url` da env (`STRIPE_SUCCESS_URL`, `STRIPE_CANCEL_URL`)
- [x] `place_order`: togliere il `print`
- [x] `PlaceOrder.cart`: Field(min_length=1)
- [x] `POST /user/register`: 409 se l'email esiste, bcrypt `$2y$`, insert + commit, `{"ok": ...}`
- [x] `Register` (modello): `Field(min_length=1)` sui campi (campi vuoti → 422, nessun utente creato)
- [x] Pulizia: default di DATABASE_URL rimosso
- [x] Pulizia: import inutilizzati (`Header`, `Text`, `declarative_base` + `Base`)

## Frontend (React)
- [x] `[id].jsx`: leggere le info del ristorante dalla risposta di /menu (stato `props`)
- [x] `[id].jsx`: rinominare lo stato `props` in `restaurant` (init `null`, un solo `{restaurant && ...}`)
- [x] `[id].jsx`: `class=` → `className=` (riga carrello vuoto) e `key` invertite sui pulsanti +/-
- [x] CartContext: salvare in sessionStorage (chiave `cart`)
- [x] CartContext: `removeFromCart` modifica `item.quantity` direttamente (con StrictMode può scalare di 2)
- [x] CartContext: togliere i `console.log` in `addToCart`; aggiornare il commento "parte come un array vuoto"
- [x] Navigazione interna con useNavigate/Link invece di window.location (resta `window.location` solo per il redirect a Stripe)
- [x] Login.jsx, RestaurantList.jsx e Register.jsx: `?.` su `e.response` nel catch (Register mostra `detail` solo se stringa)
- [x] Register.jsx: URL `api/user/register` → `/api/user/register`
- [x] main.jsx: import di Register duplicato
- [x] Register.jsx: schema zod con `name`/`lastname`
- [x] Register.jsx: rinominare la funzione `login` in `register`
- [x] Register.jsx: stato `form` con `name`/`lastname`/`errorName`/`errorLastname`
- [x] Register.jsx: errori di `name`/`lastname` nei campi giusti
- [x] Register.jsx: `FormGroup` name/lastname con `errorName`/`errorLastname`
- [x] Register.jsx: errori zod azzerati al nuovo invio
- [x] Register.jsx: messaggio di successo prima del redirect a `/login`
- [x] Chiamate protette tramite src/api.js (interceptor token + 401 → /login)
- [x] RestaurantList.jsx e [id].jsx: togliere `import axios` (non più usato)
- [ ] Rotta `/` mancante

## Specifiche (frontend/CLAUDE.md)
- [x] `backend/CLAUDE.md` e `frontend/CLAUDE.md`: la risposta di `/menu` contiene anche `restaurant`
- [ ] Dettaglio: errori 401/404 su /menu e su "Ordina"
- [ ] Carrello con ristoranti diversi: svuotare o bloccare?
- [x] Svuotare il carrello dopo il pagamento (spec + codice in /payments/ok)
- [x] Sezione Registrazione
- [x] Sezione Pagamento ok
- [x] Register: spec allineata su `lastname`

## Infrastruttura
- [x] git init (+ `.gitignore` nella radice)
- [x] Primo commit e push su `origin/main` (github.com/gaetano1984/python-food-delivery)
- [x] filament/Dockerfile mancante (ricreato: php:8.4-cli + composer)
