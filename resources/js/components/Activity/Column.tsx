function Column({ heading, value} : { heading: string, value: string }) {
    return (
        <div className="flex flex-col justify-start">
            <span className="text-sm text-muted-foreground">{heading}</span>
            <p className='text-nowrap'>{value}</p>
        </div>
    );
}

export default Column;
