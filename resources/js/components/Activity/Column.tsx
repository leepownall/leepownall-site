function Column({ heading, value} : { heading: string, value: string }) {
    return (
        <div className="mt-2 flex items-center space-x-2">
            <div>
                <span className="text-sm text-muted-foreground">{heading}</span>
                <p>{value}</p>
            </div>
        </div>
    );
}

export default Column;
