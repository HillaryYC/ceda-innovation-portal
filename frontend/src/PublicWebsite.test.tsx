// @vitest-environment jsdom
import {afterEach,beforeEach,expect,it,vi} from 'vitest';
import {act,cleanup,fireEvent,render,screen} from '@testing-library/react';
import PublicWebsite from './PublicWebsite';

beforeEach(()=>{vi.useFakeTimers();vi.stubGlobal('matchMedia',vi.fn(()=>({matches:false})))});
afterEach(()=>{cleanup();vi.useRealTimers();vi.unstubAllGlobals()});

it('shows only the first hero photo and routes sign-in to the shared login',()=>{
 render(<PublicWebsite/>);
 expect(document.querySelectorAll('.public-hero-photo')).toHaveLength(1);
 expect(document.querySelector('.public-hero-photo')?.getAttribute('src')).toBe('/hero-market.webp');
 expect(document.querySelector('.public-hero-controls')).toBeNull();
 expect(screen.getByRole('link',{name:'Awarded ideas'}).getAttribute('href')).toBe('#awarded-ideas');
 expect(document.querySelector('.public-header-cta')?.textContent).toContain('SIGN IN');
 expect(document.querySelector('.public-header-cta')?.getAttribute('href')).toBe('/login');
 expect(screen.getAllByRole('link',{name:/Access Portal/})[0].getAttribute('href')).toBe('/login');
});

it('respects reduced motion and removes repeated category icons and the extra note',()=>{
 vi.stubGlobal('matchMedia',vi.fn(()=>({matches:true})));
 render(<PublicWebsite/>);act(()=>vi.advanceTimersByTime(13000));
 expect(document.querySelector('.public-hero-photo.is-current')?.getAttribute('src')).toBe('/hero-market.webp');
 expect(document.querySelector('.public-showcase-photo img')?.getAttribute('src')).toBe('/category-agriculture.webp');
 expect(document.querySelector('.public-category-body>svg')).toBeNull();
 expect(screen.queryByText('Have something different in mind? Your idea is welcome, too.')).toBeNull();
});

it('keeps the four application cards and rotates through awarded ideas',()=>{
 render(<PublicWebsite/>);
 expect(document.querySelectorAll('.public-benefits article')).toHaveLength(4);
 expect(document.querySelector('.public-benefits [inert]')).toBeNull();
 expect(document.querySelector('#how-it-works')).toBeNull();
 expect(screen.getByRole('heading',{name:'Meet the ideas recognised by CEDA and the people bringing them to life.'})).toBeTruthy();
 expect(screen.getByText('From maize harvest to local food products')).toBeTruthy();
 expect(screen.queryByText('COMING SOON')).toBeNull();
 fireEvent.click(screen.getByRole('button',{name:'Next awarded idea'}));
 expect(screen.getByRole('heading',{name:'Handbags crafted for local and wider markets'})).toBeTruthy();
 expect(document.querySelector('.public-showcase-photo img')?.getAttribute('src')).toBe('/award-handbag.jpg');
 act(()=>vi.advanceTimersByTime(6500));
 expect(screen.getByRole('heading',{name:'Growing blueberries for fresh local markets'})).toBeTruthy();
 expect(document.querySelector('.public-showcase-photo img')?.getAttribute('src')).toBe('/award-blueberry.jpg');
 expect(screen.getAllByRole('heading',{name:'Create your account'})[0]).toBeTruthy();
 expect(screen.getAllByRole('heading',{name:'Get notified if awarded'})[0]).toBeTruthy();
});

it('shows the revised public copy and only the final process link',()=>{
 render(<PublicWebsite/>);
 expect(screen.getByText(/This platform connects Batswana innovators/)).toBeTruthy();
 expect(screen.getByRole('heading',{name:'Ideas That Build Botswana'})).toBeTruthy();
 expect(screen.getByText('How it works')).toBeTruthy();
 expect(screen.queryByText('See how it works')).toBeNull();
 expect(screen.queryByText(/FOUR SIMPLE STEPS/)).toBeNull();
 expect(document.querySelectorAll('.public-category-photo span')).toHaveLength(0);
 expect(document.querySelectorAll('.public-feature-link')).toHaveLength(1);
 expect(screen.getAllByRole('link',{name:/^Explore$/})).toHaveLength(3);
 expect(screen.getAllByRole('link',{name:/Access Portal/})).toHaveLength(1);
 expect(screen.queryByText('THE FUTURE IS HOMEGROWN')).toBeNull();
 expect(screen.getByRole('heading',{name:'Frequently asked questions'})).toBeTruthy();
 expect(document.querySelectorAll('#faq details')).toHaveLength(7);
 const firstQuestion=screen.getByText('How do I submit an idea?');
 fireEvent.click(firstQuestion);
 expect(firstQuestion.closest('details')?.open).toBe(true);
 expect(screen.getByRole('link',{name:'FAQ'}).getAttribute('href')).toBe('#faq');
});
