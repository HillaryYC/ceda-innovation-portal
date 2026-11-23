import {it,expect} from 'vitest';
import {renderToStaticMarkup} from 'react-dom/server';
import OutcomeDonut,{donutSegments} from './OutcomeDonut';
import {seed,type Idea} from './model';
it('calculates category shares with cumulative non-overlapping positions',()=>{
 const items:Idea[]=[{...seed[0],category:'A'},{...seed[1],category:'A'},{...seed[2],category:'B'}];
 const segments=donutSegments(items,'all',['A','B']);expect(segments[0].percent).toBeCloseTo(200/3);expect(segments[1].offset).toBeCloseTo(200/3);expect(segments.reduce((sum,s)=>sum+s.percent,0)).toBeCloseTo(100);
});
it('limits awarded and rejected charts to their explicit final outcomes',()=>{
 const items:Idea[]=[{...seed[0],outcome:'awarded'},{...seed[1],outcome:'adjudicator_rejected'},{...seed[2],outcome:'approved'}];
 expect(donutSegments(items,'successful',items.map(i=>i.category)).reduce((n,s)=>n+s.count,0)).toBe(1);
 expect(donutSegments(items,'unsuccessful',items.map(i=>i.category)).reduce((n,s)=>n+s.count,0)).toBe(1);
});
it('shows an honest empty chart rather than fabricated outcome shares',()=>{
 const html=renderToStaticMarkup(<OutcomeDonut ideas={seed} categories={seed.map(i=>i.category)} mode="successful" onMode={()=>{}} onCategory={()=>{}}/>);
 expect(html).toContain('No awarded recorded');expect(html).toContain('0 awarded, distributed across 0 categories');expect(html).not.toContain('NaN');
});
