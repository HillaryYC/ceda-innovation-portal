<?php

// Proposed hackathon form. Its categories and questions are versioned in form_definitions.
$q = fn (string $key, string $label, string $type = 'text', array $options = [], bool $required = true, string $hint = '') => [
    'key' => $key, 'label' => $label, 'type' => $type, 'options' => $options,
    'required' => $required, 'hint' => $hint, 'max' => $type === 'textarea' ? 3000 : 500,
];
$pick = fn ($key, $label, $options, $required = true) => $q($key, $label, 'select', $options, $required);
$many = fn ($key, $label, $options, $required = true) => $q($key, $label, 'multiselect', $options, $required);
$number = fn ($key, $label, $required = true) => $q($key, $label, 'number', [], $required);
$explain = fn ($key, $label, $required = true) => $q($key, $label, 'textarea', [], $required);

$stages = [
    'Starting or testing' => [
        $pick('prototype_progress', 'How far have you progressed?', ['Idea or plan only', 'Site, equipment or inputs arranged', 'Trial production or service completed', 'First sales made']),
        $explain('prototype_learning', 'What have you tried so far, and what did you learn?', false),
        $pick('prototype_users', 'Who has seen or bought your product or service?', ['No one yet', 'Friends and family', 'Potential customers', 'Paying customers']),
        $number('months_to_first_sale', 'Months from funding to first sale'),
    ],
    'Operating business' => [
        $number('months_operating', 'How many months have you been operating?'),
        $pick('current_sales_band', 'Current monthly sales', ['Under P5,000', 'P5,000–20,000', 'P20,000–50,000', 'Over P50,000']),
        $number('regular_customers', 'Number of regular customers'),
        $pick('current_profit', 'Is the business making a profit?', ['Yes', 'Breaking even', 'Not yet', 'Not sure']),
    ],
];
$families = [
    'Producers' => [
        $pick('land_access', 'How do you access your land or site?', ['Own', 'Family or tribal allocation', 'Leased', 'No land yet']),
        $many('water_sources', 'Which water sources can you use?', ['Borehole', 'Tap water', 'River or dam', 'Rain only', 'None yet']),
        $many('produce_buyers', 'Who will buy your produce?', ['Processor', 'Retail shops', 'Schools or institutions', 'Individuals', 'Not yet sure']),
        $explain('input_supplier', 'Where will you buy feed, seed or other main inputs?'),
    ],
    'Makers' => [
        $pick('workshop_premises', 'Where will you make your products?', ['Own premises', 'Rented premises', 'Home-based', 'No premises yet']),
        $explain('equipment_needed', 'What equipment do you have and what must you buy?'),
        $pick('material_supplier', 'How secure is the supply of your main ingredients or materials?', ['No supplier yet', 'Verbal agreement', 'Written agreement']),
        $pick('maker_orders', 'Who has ordered or promised to buy?', ['No one yet', 'A few people', 'Regular customers', 'Contracts']),
    ],
    'Sellers' => [
        $pick('shop_location', 'Where will you sell?', ['Village', 'Township', 'Town centre', 'Roadside', 'Online or mobile']),
        $pick('shop_premises', 'What premises will you use?', ['Own', 'Rented', 'Container or stall', 'None yet']),
        $number('starting_stock_value', 'Stock you need to buy (Pula)'),
        $pick('stock_source', 'Where will you buy stock?', ['Wholesaler', 'Direct from producers', 'Cross-border', 'Mixed']),
    ],
    'Services' => [
        $pick('service_location', 'Where will you work?', ['Fixed premises', 'Customer sites', 'Mobile or vehicle', 'Online']),
        $explain('service_asset', 'What main asset or equipment do you need, and what will it cost?'),
        $pick('service_pricing', 'How will you charge customers?', ['Per job', 'Per hour', 'Per person', 'Monthly contract', 'Other']),
        $pick('repeat_customers', 'Do customers return or book again?', ['Most', 'Some', 'Few', 'Too new to know']),
    ],
];
$categories = [
    'Poultry' => ['Producers', 'Chickens for meat, eggs or both.', [$pick('bird_type', 'Do you raise or plan to raise broilers, layers or both?', ['Broilers', 'Layers', 'Both', 'Other']), $number('birds_now', 'Birds you have now', false), $number('birds_planned', 'Birds planned after funding'), $pick('bird_housing', 'What poultry housing is available?', ['None', 'Basic structure', 'Poultry house']), $pick('bird_losses', 'What bird losses have you experienced?', ['Under 5%', '5–10%', 'Over 10%', 'No birds raised yet', 'Not recorded'])]],
    'Vegetables and crops' => ['Producers', 'Horticulture and crop production.', [$q('main_crops', 'Which crops will you grow?'), $pick('irrigation', 'How will you irrigate?', ['Drip', 'Sprinkler', 'Flood or hose', 'None']), $pick('crop_storage', 'How will you store the harvest?', ['No storage', 'Shade or shed', 'Cold room']), $explain('harvest_calendar', 'When will you plant, harvest and sell?')]],
    'Livestock' => ['Producers', 'Pigs, goats, sheep, dairy or beef.', [$many('animal_types', 'Which animals will you raise?', ['Pigs', 'Goats or sheep', 'Dairy cattle', 'Beef cattle']), $number('herd_now', 'Animals you have now', false), $number('herd_planned', 'Animals planned after funding'), $pick('feed_source', 'What is your main feed source?', ['Own grazing', 'Own fodder', 'Bought feed', 'Mixed'])]],
    'Bricks and blocks' => ['Makers', 'Manufacturing building blocks.', [$explain('sand_cement_source', 'Where will you get sand and cement?'), $pick('block_machine', 'How will you access a block machine?', ['Own', 'Hire', 'Need to buy']), $number('blocks_per_day', 'Blocks you expect to produce per day'), $number('block_price', 'Selling price per block (Pula)')]],
    'Furniture and metalwork' => ['Makers', 'Carpentry, furniture, welding and steel.', [$pick('trade', 'What is your main trade?', ['Furniture and carpentry', 'Welding and steel', 'Both']), $pick('skill_proof', 'How did you learn the trade?', ['Trade certificate', 'Apprenticeship', 'Self-taught with portfolio']), $number('orders_in_hand', 'Number of current orders', false), $explain('timber_steel_source', 'Where will you buy timber or steel?')]],
    'Garments and textiles' => ['Makers', 'Clothing, uniforms and textile products.', [$many('garment_products', 'What will you make?', ['School uniforms', 'Corporate wear', 'Traditional wear', 'Fashion', 'Other']), $number('sewing_machines', 'Sewing machines you have now', false), $number('garments_per_week', 'Garments you expect to make each week'), $explain('fabric_source', 'Where will you source fabric?')]],
    'Food and drinks' => ['Makers', 'Bakery, packaged food and beverages.', [$explain('food_products', 'What will you make and how long will it keep?'), $pick('food_inspection', 'Status of the relevant health inspection', ['Have approval', 'Applying', 'Not yet', 'Not sure what is required']), $pick('food_packaging', 'How will you package and label it?', ['Plain bags or boxes', 'Branded packaging', 'Ingredients and expiry labelled']), $pick('food_storage', 'What storage is available?', ['Shelving', 'Fridge or freezer', 'Cold room'])]],
    'Tuckshop and general dealer' => ['Sellers', 'Everyday goods and convenience retail.', [$many('extra_services', 'What services will you offer beyond goods?', ['Airtime or electricity', 'Mobile money', 'Photocopying', 'None']), $pick('sales_on_credit', 'Will you sell on credit?', ['Never', 'Sometimes', 'Often']), $pick('shop_cooling', 'Do you have a fridge or freezer?', ['Have one', 'Need one'])]],
    'Butchery' => ['Sellers', 'Retail meat sales.', [$many('meat_suppliers', 'Where will you buy meat?', ['Abattoir', 'Local farmers', 'Wholesaler']), $pick('meat_inspection', 'Status of the relevant meat handling approval', ['Have approval', 'Applying', 'Not yet', 'Not sure what is required']), $pick('meat_cooling', 'What cold storage is available?', ['Cold room', 'Chest freezers', 'None']), $number('meat_kg_week', 'Kilograms you expect to sell each week')]],
    'Hardware' => ['Sellers', 'Building materials and tools.', [$many('hardware_lines', 'What will you stock?', ['Cement and bricks', 'Plumbing', 'Electrical', 'Paint', 'Tools', 'Roofing']), $pick('hardware_storage', 'What storage space is available?', ['Yard', 'Warehouse', 'Small shop only']), $pick('delivery_vehicle', 'How will you deliver goods?', ['Own vehicle', 'Hire', 'No delivery yet'])]],
    'Fresh produce reselling' => ['Sellers', 'Buying and reselling produce.', [$many('produce_lines', 'What will you resell?', ['Vegetables', 'Fruit', 'Eggs', 'Dried goods', 'Other']), $many('produce_sources', 'Where will you buy?', ['Farmers', 'Wholesale market', 'Cross-border']), $pick('resale_cooling', 'How will you store produce?', ['No storage', 'Shade', 'Cold room', 'Refrigerated vehicle']), $pick('produce_spoilage', 'How much stock may spoil or go unsold?', ['Under 5%', '5–15%', 'Over 15%', 'Not sure'])]],
    'Transport' => ['Services', 'Passenger or goods transport.', [$pick('vehicle_type', 'What vehicle or service will you offer?', ['Combi', 'Taxi', 'Delivery van', 'Truck', 'Bus']), $number('vehicles_needed', 'Vehicles you need to buy', false), $pick('transport_permission', 'Status of the relevant transport permit', ['Have it', 'Applying', 'Not yet', 'Not sure what is required']), $explain('route_demand', 'Which route or area will you serve, and what demand have you found?'), $number('fuel_maintenance', 'Monthly fuel and maintenance budget (Pula)')]],
    'Construction' => ['Services', 'Building and contracting services.', [$explain('past_projects', 'Describe up to three completed projects, if any', false), $explain('construction_equipment', 'What equipment do you own or hire?'), $pick('contracts_in_hand', 'What work is in hand?', ['None', 'Applied for tenders', 'Contracts won']), $many('skilled_staff', 'Which skills are on your team?', ['Engineer or technician', 'Foreman', 'Artisans', 'General labour'])]],
    'Salon and personal services' => ['Services', 'Salon, barber, car wash or laundry.', [$pick('personal_service', 'What service will you offer?', ['Salon', 'Barber', 'Car wash', 'Laundry', 'Other']), $explain('personal_equipment', 'What equipment do you have and need?'), $many('site_utilities', 'What utilities are available?', ['Mains water', 'Borehole', 'Mains power', 'Solar or generator']), $number('price_per_customer', 'Average price per customer (Pula)')]],
    'Catering' => ['Services', 'Meals for events, organisations and customers.', [$many('catering_customers', 'Who will you serve?', ['Events', 'Companies', 'Schools', 'Government', 'Individuals']), $pick('kitchen', 'What kitchen will you use?', ['Approved commercial kitchen', 'Home kitchen', 'Rented kitchen', 'None yet']), $pick('food_handler_status', 'Status of the relevant food handling approval', ['Have approval', 'Applying', 'Not yet', 'Not sure what is required']), $pick('catering_bookings', 'How many bookings are in hand?', ['None', 'A few', 'Regular'])]],
    'Guesthouse and daycare' => ['Services', 'Accommodation or childcare.', [$pick('care_or_stay', 'What will you operate?', ['Guesthouse or lodge', 'Daycare or preschool']), $pick('care_premises', 'What premises are available?', ['Own', 'Rented', 'Building in progress']), $number('care_capacity', 'How many rooms, beds or children can you serve?'), $pick('care_approval', 'Status of the relevant registration or inspection', ['Have approval', 'Applying', 'Not yet', 'Not sure what is required'])]],
    'ICT and digital' => ['Services', 'Software, design, repairs or digital services.', [$many('digital_offer', 'What will you offer?', ['Software or app', 'Web or design', 'Repairs and hardware', 'Training', 'Internet or networking']), $explain('digital_revenue', 'Who pays you and how?'), $pick('digital_traction', 'What evidence of customers do you have?', ['None yet', 'Pilot users', 'Paying clients']), $q('digital_portfolio', 'Link to a demo, website or portfolio', 'text', [], false)]],
];

$definition = [
    'mode' => 'online', 'layout' => 'smart-v1', 'version' => 'hackathon-smart-v2',
    'stages' => array_keys($stages),
    'core' => [
        'about' => [$pick('applying_as', 'How are you applying?', ['Individual', 'Group', 'Registered company', 'Cooperative']), $number('team_size', 'How many people are in your team?'), $pick('experience', 'Experience in this field', ['None', 'Under 2 years', '2–5 years', 'Over 5 years']), $many('training', 'What training does your team have?', ['Formal qualification', 'Short course', 'Apprenticeship', 'Self-taught', 'None'])],
        'idea' => [$many('customer_groups', 'Who are your customers?', ['Households', 'Shops and retail', 'Restaurants', 'Government', 'Other businesses', 'Other']), $pick('demand_test', 'How have you tested demand?', ['Not yet', 'Spoke to customers', 'Have pre-orders', 'Have paying customers']), $explain('competitive_edge', 'What makes your offer different?')],
        'funding' => [$number('amount_requested', 'Amount requested (Pula)'), $number('use_equipment', 'Equipment (Pula)', false), $number('use_stock', 'Stock (Pula)', false), $number('use_premises', 'Building or premises (Pula)', false), $number('use_working_capital', 'Working capital (Pula)', false), $number('use_marketing', 'Marketing (Pula)', false), $number('use_other', 'Other uses (Pula)', false), $pick('own_contribution', 'Your own contribution', ['None', 'Under 10%', '10–30%', 'Over 30%']), $number('expected_sales', 'Expected monthly sales (Pula)'), $number('expected_costs', 'Expected monthly costs (Pula)'), $number('jobs_to_create', 'How many people will you employ?'), $pick('employment_type', 'Will the jobs be full-time or part-time?', ['Full-time', 'Part-time', 'Both']), $explain('biggest_risk', 'What is the biggest risk, and how will you handle it?')],
    ],
    'categories' => [],
];
foreach ($categories as $name => [$family, $description, $specific]) {
    $questions = [];
    foreach ($stages as $stage => $items) foreach ($items as $item) $questions[] = [...$item, 'stages' => [$stage], 'group' => 'Your progress'];
    foreach ($families[$family] as $item) $questions[] = [...$item, 'group' => $family];
    foreach ($specific as $item) $questions[] = [...$item, 'group' => $name];
    $definition['categories'][$name] = ['family' => $family, 'description' => $description, 'questions' => $questions];
}
return $definition;
