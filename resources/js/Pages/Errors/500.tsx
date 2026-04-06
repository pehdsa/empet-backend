export default function ServerError() {
    return (
        <div className="flex min-h-screen items-center justify-center bg-muted/40">
            <div className="text-center">
                <h1 className="text-6xl font-bold text-muted-foreground">500</h1>
                <p className="mt-2 text-lg text-muted-foreground">
                    Erro interno do servidor
                </p>
                <p className="mt-1 text-sm text-muted-foreground">
                    Tente novamente em alguns instantes.
                </p>
            </div>
        </div>
    );
}
