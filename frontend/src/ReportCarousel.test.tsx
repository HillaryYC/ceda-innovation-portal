// @vitest-environment jsdom
import {afterEach,expect,it} from 'vitest';
import {cleanup,fireEvent,render,screen} from '@testing-library/react';
import ReportCarousel from './ReportCarousel';
afterEach(cleanup);
it('switches the whole report panel with buttons and keyboard without wrapping',()=>{
 render(<ReportCarousel><p>Donut content</p><p>Map content</p></ReportCarousel>);
 expect(screen.getByRole('button',{name:'Previous report view'}).hasAttribute('disabled')).toBe(true);
 fireEvent.click(screen.getByRole('button',{name:'Next report view'}));
 expect(screen.getByRole('group',{name:'2 of 2: Locations'}).getAttribute('aria-hidden')).toBe('false');
 expect(screen.getByRole('button',{name:'Next report view'}).hasAttribute('disabled')).toBe(true);
 fireEvent.keyDown(screen.getByRole('region',{name:'Programme report views'}),{key:'ArrowLeft'});
 expect(screen.getByRole('group',{name:'1 of 2: Outcomes'})).toBeTruthy();
});
it('supports horizontal swipes while ignoring vertical scrolling',()=>{
 render(<ReportCarousel><p>Donut content</p><p>Map content</p></ReportCarousel>);
 const viewport=document.querySelector('.report-carousel-window')!;
 fireEvent.touchStart(viewport,{touches:[{clientX:200,clientY:100}]});fireEvent.touchEnd(viewport,{changedTouches:[{clientX:190,clientY:250}]});
 expect(screen.getByRole('group',{name:'1 of 2: Outcomes'})).toBeTruthy();
 fireEvent.touchStart(viewport,{touches:[{clientX:200,clientY:100}]});fireEvent.touchEnd(viewport,{changedTouches:[{clientX:50,clientY:110}]});
 expect(screen.getByRole('group',{name:'2 of 2: Locations'})).toBeTruthy();
});
