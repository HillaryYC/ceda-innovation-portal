<?php
// Version this definition when changing questions for future submissions.
$question = fn($key, $label, $hint) => compact('key', 'label', 'hint') + ['required'=>true, 'max'=>3000];
$form = [
 'version'=>'business-proposal-v2','mode'=>'document',
 'stages'=>['Idea / concept','Prototype / pilot','Operating business','Growing business'],
 'categories'=>[
  'Agriculture'=>['description'=>'Farming, livestock, food production and agricultural services.','questions'=>[
   $question('activity','What will you produce or provide?','Describe the crops, livestock, products or services.'),
   $question('resources','What resources will the business need?','Consider land, water, equipment, skills and seasonal needs.'),
   $question('market','How will you reach your buyers?','Explain who will buy, how you will deliver and any existing demand.')]],
  'Financial inclusion'=>['description'=>'Products and services that improve access to financial tools.','questions'=>[
   $question('access','What financial access problem are you addressing?','Explain who is underserved and why existing options do not meet their needs.'),
   $question('service','How will your service work?','Describe how customers will use the product and any partners needed.'),
   $question('protection','How will you protect customers?','Consider affordability, personal information and risks to customers.')]],
  'Green innovation'=>['description'=>'Ideas that reduce environmental harm or use resources more efficiently.','questions'=>[
   $question('impact','What environmental problem will your idea address?','Describe the waste, emissions or resource challenge.'),
   $question('measurement','How will you measure the environmental benefit?','Explain the improvement you expect and how you will track it.'),
   $question('operations','How will the solution operate sustainably?','Consider inputs, collection, processing and the business model.')]],
  'Youth & employment'=>['description'=>'Ideas that create work, develop skills or improve access to employment.','questions'=>[
   $question('beneficiaries','Who will benefit from this idea?','Describe the people you intend to support and the barriers they face.'),
   $question('opportunities','What work or skills opportunities will you create?','Explain the jobs, training or services you will offer.'),
   $question('outcomes','How will you track progress?','Describe how you will measure participation, skills or employment outcomes.')]],
  'Tourism'=>['description'=>'Visitor experiences, travel services and tourism businesses.','questions'=>[
   $question('experience','What visitor experience or service will you offer?','Describe the destination, activity or service and what makes it distinctive.'),
   $question('visitors','Which visitors will you serve and how will you reach them?','Consider local and international visitors, booking and promotion.'),
   $question('community','How will local communities and places benefit?','Consider local partners, cultural heritage and environmental impact.')]],
  'Health'=>['description'=>'Ideas that improve health, wellbeing or access to care.','questions'=>[
   $question('need','What health or wellbeing need are you addressing?','Describe the people affected and the gap in current services.'),
   $question('delivery','How will your solution reach the people who need it?','Explain delivery, accessibility and any professional partners.'),
   $question('safety','How will you manage safety and privacy?','Describe relevant safeguards and approvals you may need.')]],
 ],
];

foreach($form['categories'] as $name=>&$category){$category['template_slug']=strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$name),'-'));}
unset($category);
return $form;
