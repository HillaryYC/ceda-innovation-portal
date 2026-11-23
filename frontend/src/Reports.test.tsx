import {it,expect} from 'vitest';
import {renderToStaticMarkup} from 'react-dom/server';
import Reports from './Reports';
import {seed,normaliseIdea} from './model';
it('renders a lean application summary with details initially collapsed',()=>{
 const html=renderToStaticMarkup(<Reports section="applications" ideas={seed.map(normaliseIdea)} onOpen={()=>{}}/>);
 expect(html).not.toContain('>View details<');expect(html).toContain('View Total submissions applications');expect(html).toContain('Outcomes by category');expect(html).not.toContain('donut-title-icon');expect(html).not.toContain('<table');expect(html).not.toContain('Export CSV');
});
it('does not count legacy closed records as adjudicator rejections',()=>{
 const html=renderToStaticMarkup(<Reports section="outcomes" ideas={[{...seed[0],status:'ineligible'}]} onOpen={()=>{}}/>);
 expect(html).toContain('Received a final award');expect(html).toContain('Unsuccessful');
});
