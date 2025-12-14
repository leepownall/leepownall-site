import * as React from 'react';
import { LucideIcon } from 'lucide-react';

import { cn } from '@/lib/utils';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

interface StatWithIconProps extends React.ComponentProps<'div'> {
    icon: LucideIcon;
    value: string | number;
    iconClassName?: string;
    valueClassName?: string;
    tooltip?: string;
}

function StatWithIcon({
    icon: Icon,
    value,
    className,
    iconClassName,
    valueClassName,
    tooltip,
    ...props
}: StatWithIconProps) {
    const content = (
        <div
            className={cn('flex items-center gap-2', className)}
            {...props}
        >
            <Icon className={cn('h-4 w-4 text-muted-foreground', iconClassName)} />
            <span className={cn(valueClassName)}>{value}</span>
        </div>
    );

    if (tooltip) {
        return (
            <Tooltip>
                <TooltipTrigger asChild>
                    {content}
                </TooltipTrigger>
                <TooltipContent>
                    <p>{tooltip}</p>
                </TooltipContent>
            </Tooltip>
        );
    }

    return content;
}

export { StatWithIcon };
