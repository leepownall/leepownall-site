function Stat({ heading, value, visible = true }: { heading: string; value: string; visible?: boolean }) {
    if (!visible) {
        return null;
    }

    return (
        <div className="flex flex-col justify-start">
            <span className="text-sm text-muted-foreground">{heading}</span>
            <p>{value}</p>
        </div>
    );
}

export default Stat;
