import { HTMLAttributes } from 'react';

export default function AppLogoIcon(props: HTMLAttributes<HTMLImageElement>) {
    return (
        <img
            src="/logo.png" // Path to the logo
            alt="App Logo"
            {...props} // Spread other props for further customization
        />
    );
}