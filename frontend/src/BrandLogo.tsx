import './brand-logo.css';

type BrandLogoProps={variant?:'light'|'dark';size?:'compact'|'standard'|'large';label?:string};

/** Choose the logo by the surface behind it, not by the page theme. */
export default function BrandLogo({variant='light',size='standard',label='CEDA'}:BrandLogoProps){
 return <span className={`ceda-brand-logo ceda-brand-logo-${variant} ceda-brand-logo-${size}`} role="img" aria-label={label}><img src={variant==='dark'?'/ceda-logo-dark.png':'/ceda-logo-light.png'} alt="" aria-hidden="true"/></span>;
}
