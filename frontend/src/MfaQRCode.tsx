import {QRCodeSVG} from 'qrcode.react';

export type MfaSetup={secret:string;issuer:string;account:string};

export function otpAuthUrl({secret,issuer,account}:MfaSetup){
 return `otpauth://totp/${encodeURIComponent(issuer)}:${encodeURIComponent(account)}?secret=${encodeURIComponent(secret)}&issuer=${encodeURIComponent(issuer)}&algorithm=SHA1&digits=6&period=30`;
}

export default function MfaQRCode({setup}: {setup:MfaSetup}){
 return <div className="mfa-setup"><p>In Google Authenticator, tap <strong>+</strong>, choose <strong>Scan a QR code</strong>, and point your phone at this code.</p><div className="mfa-qr" role="img" aria-label="Authenticator setup QR code"><QRCodeSVG value={otpAuthUrl(setup)} size={210} marginSize={0} level="M"/></div><p>Then enter the six-digit code shown in your app below.</p><details><summary>Can’t scan? Enter a setup key instead</summary><p>In the authenticator, choose <strong>Enter a setup key</strong>. Use a time-based account named {setup.account}.</p><code>{setup.secret}</code></details></div>;
}
