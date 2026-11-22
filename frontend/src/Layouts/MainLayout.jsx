import React from 'react';
// Importa header e footer se sono componenti separati
import Header from './Header'; 
import Footer from './Footer';

export default function MainLayout({ children }) {
  return (
    <div className="flex flex-col min-h-screen">
      {/* Header fisso del vecchio layout Laravel */}
      <Header />

      {/* Contenuto dinamico della pagina (il vecchio @yield) */}
      <main className="flex-grow container mx-auto p-4">
        {children}
      </main>

      {/* Footer fisso */}
      <Footer />
    </div>
  );
}