import { useContext, useEffect } from "react"
import { CartContext } from "./context/CartContext"

export default function Ok(){
    const {clearCart} = useContext(CartContext);

    useEffect(() => {
        clearCart();
    }, []);

    return (
        <div>
            <div className="flex items-center justify-center h-screen">
                <div className="w-full max-w-sm border border-gray-300 rounded-lg">
                    <div className="bg-blue-300 p-2 text-center font-semibold">
                        Success!
                    </div>
                    <div className="grid grid-cols-1 gap-4 p-2">
                        Pagamento andato a buon fine.<br/>
                        L'ordine vi verrà consegnato a breve <br/>
                    </div>                    
                    <div className="grid grid-cols-1 gap-4 p-2 text-center">
                        <a href="/restaurants/list">Torna alla home</a>
                    </div>
                </div>
            </div>
        </div>
    )
}
