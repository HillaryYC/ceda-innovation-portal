// @vitest-environment jsdom
import {afterEach,expect,it} from 'vitest';
import {cleanup,render,screen} from '@testing-library/react';
import MfaQRCode,{otpAuthUrl} from './MfaQRCode';
afterEach(cleanup);
const setup={secret:'JBSWY3DPEHPK3PXP',issuer:'CEDA Innovation Portal',account:'reviewer@example.com'};
it('encodes a local time-based authenticator QR payload',()=>{const url=new URL(otpAuthUrl(setup));expect(url.protocol).toBe('otpauth:');expect(url.hostname).toBe('totp');expect(url.pathname).toBe('/CEDA%20Innovation%20Portal:reviewer%40example.com');expect(url.searchParams.get('secret')).toBe(setup.secret);expect(url.searchParams.get('issuer')).toBe(setup.issuer);expect(url.searchParams.get('digits')).toBe('6');expect(url.searchParams.get('period')).toBe('30')});
it('shows a scannable QR and keeps the manual key as a fallback',()=>{render(<MfaQRCode setup={setup}/>);expect(screen.getByRole('img',{name:'Authenticator setup QR code'}).querySelector('svg')).toBeTruthy();expect(screen.getByText('Can’t scan? Enter a setup key instead')).toBeTruthy()});
