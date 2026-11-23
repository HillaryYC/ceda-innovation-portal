export type AwardedStory={
 slug:string;
 title:string;
 category:string;
 excerpt:string;
 story:string;
 image:string;
 imageAlt:string;
 imageFit?:'cover'|'contain';
 imagePosition?:string;
};

// Illustrative local-preview content. Replace with a recipient-approved story before publication.
export const awardedStories:AwardedStory[]=[{
 slug:'maize-value-at-home',
 title:'From maize harvest to local food products',
 category:'Agriculture & food',
 excerpt:'A maize processing idea that could give growers a steadier local market and bring more value to each harvest.',
 story:'This idea brings local growers and small food producers together to turn harvested maize into packaged products for nearby shops and households. Processing closer to the farms could help reduce losses after harvest and keep more of the value within the community.\n\nThe proposed next step is to test a small processing model with farmers, refine the products and packaging, and learn what local customers need most.',
 image:'/category-agriculture.webp',
 imageAlt:'Growers celebrating a maize harvest in a field',
 imagePosition:'center 50%',
},{
 slug:'handbags-crafted-locally',
 title:'Handbags crafted for local and wider markets',
 category:'Manufacturing & creative industries',
 excerpt:'A handbag workshop idea combining careful making with opportunities for local sales and skills development.',
 story:'This idea centres on producing durable, thoughtfully designed handbags in a small local workshop. It brings together practical craft skills, product design and a route to market for accessories made in Botswana.\n\nThe proposed next step is to refine a small collection, test demand with customers and retailers, and build a reliable production process as orders grow.',
 image:'/award-handbag.jpg',
 imageAlt:'A craftsperson making a leather handbag at a workbench',
 imageFit:'contain',
},{
 slug:'blueberries-grown-locally',
 title:'Growing blueberries for fresh local markets',
 category:'Agriculture & food',
 excerpt:'A blueberry growing idea exploring how protected cultivation could bring fresh fruit to more local customers.',
 story:'This idea explores growing blueberries under protected conditions and supplying fresh fruit to households, shops and hospitality businesses. A controlled growing environment could help the venture learn which varieties and methods work best locally.\n\nThe proposed next step is to test production at a manageable scale, measure quality and yield, and build dependable routes from the grower to the customer.',
 image:'/award-blueberry.jpg',
 imageAlt:'Blueberry plants growing in pots inside a covered tunnel',
 imagePosition:'center 52%',
}];
