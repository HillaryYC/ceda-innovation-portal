// @vitest-environment jsdom
import {afterEach,expect,it,vi} from 'vitest';
import {cleanup,fireEvent,render,screen} from '@testing-library/react';
import BotswanaSubmissionMap from './BotswanaSubmissionMap';
import {seed} from './model';
afterEach(cleanup);
it('shows category pins, opens their application and reports unknown locations',()=>{
 const onOpen=vi.fn();render(<BotswanaSubmissionMap ideas={[{...seed[0],location:'Mochudi'},{...seed[1],location:'Unrecognised place'}]} onOpen={onOpen}/>);
 expect(screen.getByRole('button',{name:'Kgatleng district, 1 submissions'})).toBeTruthy();
 fireEvent.click(screen.getByRole('button',{name:'Mochudi, Agriculture, 1 submissions'}));
 fireEvent.click(screen.getByRole('button',{name:/Turning harvests into opportunity/}));expect(onOpen).toHaveBeenCalledWith(seed[0].id);
 fireEvent.click(screen.getByRole('button',{name:'1 unmapped'}));expect(screen.getByText('Unrecognised place · View')).toBeTruthy();
});
