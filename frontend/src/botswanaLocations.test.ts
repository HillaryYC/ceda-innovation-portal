import {expect,it} from 'vitest';
import {districts,locateSubmission,mapSubmissions} from './botswanaLocations';
import {seed} from './model';
it('uses district geometry and recognises Botswana towns without remote geocoding',()=>{
 expect(districts).toHaveLength(10);expect(districts.some(d=>d.name==='Kgatleng')).toBe(true);
 expect(locateSubmission('Gaborone')?.name).toBe('Gaborone');
 expect(locateSubmission('Mochudi, Botswana')?.district).toBe('BW-KL');
 expect(locateSubmission('Maun')?.district).toBe('BW-NW');
 expect(locateSubmission('Kang')?.district).toBe('BW-KG');
 expect(locateSubmission('Kgatleng district')?.approximate).toBe(true);
});
it('does not invent coordinates for unknown, foreign or ambiguous locations',()=>{
 expect(locateSubmission('North')).toBeNull();expect(locateSubmission('My unnamed village')).toBeNull();
 expect(locateSubmission('Gaborone, Francistown')).toBeNull();expect(locateSubmission('Pretoria, South Africa')).toBeNull();
});
it('groups repeated town/category submissions and keeps unknowns in totals',()=>{
 const data=[{...seed[0],location:'Maun',category:'Agriculture'},{...seed[1],location:'Maun',category:'Agriculture'},{...seed[2],location:'Maun',category:'Health'},{...seed[3],location:'Unknown village',category:'Health'}];
 const result=mapSubmissions(data);expect(result.mapped).toBe(3);expect(result.groups).toHaveLength(2);expect(result.groups[0].ideas).toHaveLength(2);expect(result.unmapped).toHaveLength(1);
 const filtered=mapSubmissions(data,'Health');expect(filtered.mapped).toBe(1);expect(filtered.unmapped).toHaveLength(1);
});
