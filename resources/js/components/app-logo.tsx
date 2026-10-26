import BrandMark from './brand-mark';

export default function AppLogo() {
    return (
        <>
            <div className="bg-forest flex aspect-square size-8 items-center justify-center rounded-md text-white">
                <BrandMark className="h-5 w-auto" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="font-display mb-0.5 truncate leading-none font-semibold">Los Sueños</span>
            </div>
        </>
    );
}
