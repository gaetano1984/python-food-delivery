export default function Ko(){
    return (
        <div>
            <div className="flex items-center justify-center h-screen">
                <div className="w-full max-w-sm border border-gray-300 rounded-lg">
                    <div className="bg-blue-300 p-2 text-center font-semibold">
                        Success!
                    </div>
                    <div className="grid grid-cols-1 gap-4 p-2">
                        Si è verificato un errore nel pagamento.<br/>
                        Riprovare più tardi.
                    </div>                    
                    <div className="grid grid-cols-1 gap-4 p-2 text-center">
                        <a href="/restaurants/list">Torna alla home</a>
                    </div>
                </div>
            </div>
        </div>
    )
}
