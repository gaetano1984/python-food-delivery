import React from 'react'
import ReactDOM from 'react-dom/client'
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import Register from './Register.jsx' // Importa il tuo vecchio componente
import Login from './Login.jsx' // Importa il tuo vecchio componente
import RestaurantList from './RestaurantList.jsx' // Importa il tuo vecchio componente
import Restaurant from './[id].jsx' // Importa il tuo vecchio componente
import Ok from './Ok.jsx'
import Ko from './KO.jsx'
import './app.css';
import { CartProvider } from './context/CartContext.jsx';

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <BrowserRouter>
      <CartProvider>
        <Routes>
          <Route path="/register" element={<Register />} />
          <Route path="/login" element={<Login />} />
          <Route path="/restaurants/list" element={<RestaurantList /> } />
          <Route path="/restaurants/:id" element={<Restaurant /> } />
          <Route path="/payments/ok" element={<Ok /> } />
          <Route path="/payments/ko" element={<Ko /> } />
        </Routes>
      </CartProvider>      
    </BrowserRouter>
  </React.StrictMode>,
)