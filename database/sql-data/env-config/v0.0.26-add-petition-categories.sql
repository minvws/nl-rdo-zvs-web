DO $$

DECLARE

    dep_uuid_team_c UUID;
    dep_uuid_team_a UUID;
    dep_uuid_team_b UUID;
    dep_uuid_wjz_bb UUID;

BEGIN

    SELECT "id" FROM "departments" WHERE "slug"='team-a' INTO dep_uuid_team_a;
    SELECT "id" FROM "departments" WHERE "slug"='team-b' INTO dep_uuid_team_b;
    SELECT "id" FROM "departments" WHERE "slug"='team-c' INTO dep_uuid_team_c;
    SELECT "id" FROM "departments" WHERE "slug"='wjz-bb' INTO dep_uuid_wjz_bb;

    TRUNCATE "petition_categories";
    INSERT INTO "petition_categories" ("id", "department_id", "name", "created_at", "updated_at") VALUES
    ('1effff0e-24dd-6401-417c-022bba37b15d', dep_uuid_team_c, 'Woo regulier', NOW(), NOW()),
    ('1effff0e-24dd-6402-bdbb-9429adcd19c3', dep_uuid_team_c, 'Woo Covid', NOW(), NOW()),
    ('1effff0a-3c95-6791-c2ed-250facdede69', dep_uuid_wjz_bb, 'Alcoholwet LCSH', NOW(), NOW()),
    ('1effff0a-3c95-6792-a642-c3903d20d1c5', dep_uuid_wjz_bb, 'AVG', NOW(), NOW()),
    ('1effff0a-3c95-6793-c38f-f4d1693686e0', dep_uuid_wjz_bb, 'AWB', NOW(), NOW()),
    ('1effff0a-3c95-6794-883f-385e22c33775', dep_uuid_wjz_bb, 'Cobonus', NOW(), NOW()),
    ('1effff0a-3c95-6795-f6da-229049adbb98', dep_uuid_wjz_bb, 'Erkenning expertisecentrum zeldzame aandoening', NOW(), NOW()),
    ('1effff0a-3c95-6796-7c21-4ef6414ed91b', dep_uuid_wjz_bb, 'Experiment gesloten coffeeshopketen', NOW(), NOW()),
    ('1effff0a-3c95-6797-52d7-cb046771b272', dep_uuid_wjz_bb, 'Geneesmiddelenwet (CIBG)', NOW(), NOW()),
    ('1effff0a-3c97-6ea0-6b77-2903ed028d62', dep_uuid_wjz_bb, 'Geneesmiddelenwet (IGJ)', NOW(), NOW()),
    ('1effff0a-3c97-6ea1-c66d-7dd94830b98d', dep_uuid_wjz_bb, 'Instellingssubsidie Patiënten en gehandicaptenorga', NOW(), NOW()),
    ('1effff0a-3c97-6ea2-a428-5327e3799c0f', dep_uuid_wjz_bb, 'Instellingssubsidie Vinex', NOW(), NOW()),
    ('1effff0a-3c97-6ea3-e7f7-121d416a5816', dep_uuid_wjz_bb, 'Jaarverantwoording combi invorderingsbesluit', NOW(), NOW()),
    ('1effff0a-3c97-6ea4-3bc0-833dd844a126', dep_uuid_wjz_bb, 'Jaarverantwoording combi last onder dwangsom', NOW(), NOW()),
    ('1effff0a-3c97-6ea5-2ac7-e0b9f4dc589c', dep_uuid_wjz_bb, 'Jaarverantwoording Jeugd invorderingsbesluit', NOW(), NOW()),
    ('1effff0a-3c97-6ea6-0005-d5508e084f77', dep_uuid_wjz_bb, 'Jaarverantwoording Jeugd last onder dwangsom', NOW(), NOW()),
    ('1effff0a-3c97-6ea7-d81f-fbba52b2866f', dep_uuid_wjz_bb, 'Jeugdwet', NOW(), NOW()),
    ('1effff0a-3c97-6ea8-21ec-8402d2a72ec1', dep_uuid_wjz_bb, 'Openbaarmaking Gezondheidswet', NOW(), NOW()),
    ('1effff0a-3c97-6ea9-bd22-910be1f976c5', dep_uuid_wjz_bb, 'Openbaarmaking Jeugdwet', NOW(), NOW()),
    ('1effff0a-3c97-6eaa-1c98-efed94997c2b', dep_uuid_wjz_bb, 'Opiumwet', NOW(), NOW()),
    ('1effff0a-3c97-6eab-25c3-3be612f9d4c5', dep_uuid_wjz_bb, 'Palliatieve terminiale zorg en geestelijke verzorg', NOW(), NOW()),
    ('1effff0a-3c97-6eac-36f0-181f7b7f72e3', dep_uuid_wjz_bb, 'Regeling Zorgmedewerkers met langdurige post-COVID', NOW(), NOW()),
    ('1effff0a-3c97-6ead-1cb7-d0a963cb438b', dep_uuid_wjz_bb, 'Registratiebesluit BIG', NOW(), NOW()),
    ('1effff0a-3c97-6eae-7f23-227a95b47cdc', dep_uuid_wjz_bb, 'Specifieke Uitkering Meerkosten Energie Openbare Z', NOW(), NOW()),
    ('1effff0a-3c97-6eaf-a1d2-f85a7d141ba4', dep_uuid_wjz_bb, 'Subsidie borstprothesen transvrouwen', NOW(), NOW()),
    ('1effff0a-3c97-6eb0-af82-2cd6c506b1e2', dep_uuid_wjz_bb, 'Subsidieaanvraag Veerkracht en Zeggenschap', NOW(), NOW()),
    ('1effff0a-3c97-6eb1-4138-9a2bbfec9e59', dep_uuid_wjz_bb, 'Subsidieregeling (ont)regelprojecten zorgaanbieder', NOW(), NOW()),
    ('1effff0a-3c97-6eb2-f3f8-e97331a1fa97', dep_uuid_wjz_bb, 'Subsidieregeling Abortusklinieken', NOW(), NOW()),
    ('1effff0a-3c97-6eb3-8f80-711c7df3ed70', dep_uuid_wjz_bb, 'Subsidieregeling behoud langdurig zieke zorgwerkne', NOW(), NOW()),
    ('1effff0a-3c97-6eb4-ec75-aef696be5e96', dep_uuid_wjz_bb, 'Subsidieregeling Bouw en onderhoud sportaccomodati', NOW(), NOW()),
    ('1effff0a-3c97-6eb5-a539-c53d7f73390d', dep_uuid_wjz_bb, 'Subsidieregeling Brancheopleidingen LGF 2022-2024', NOW(), NOW()),
    ('1effff0a-3c97-6eb6-d07e-578ef618bde2', dep_uuid_wjz_bb, 'Subsidieregeling collectieve erkenning van Indisch', NOW(), NOW()),
    ('1effff0a-3c97-6eb7-5ff5-2bdb801f2be1', dep_uuid_wjz_bb, 'Subsidieregeling Coronabanen in de zorg', NOW(), NOW()),
    ('1effff0a-3c97-6eb8-f1f4-fa292f1d06d4', dep_uuid_wjz_bb, 'Subsidieregeling donatie bij leven', NOW(), NOW()),
    ('1effff0a-3c97-6eb9-ca89-97b391b4b33f', dep_uuid_wjz_bb, 'Subsidieregeling gratis VOG voor vrijwilligers', NOW(), NOW()),
    ('1effff0a-3c97-6eba-f817-efea4fa62d02', dep_uuid_wjz_bb, 'Subsidieregeling Heroinebehandeling', NOW(), NOW()),
    ('1effff0a-3c97-6ebb-584f-ad4da03985cd', dep_uuid_wjz_bb, 'Subsidieregeling Intergenerationeel wonen 2023', NOW(), NOW()),
    ('1effff0a-3c97-6ebc-3ab1-bf6bdf7b01c8', dep_uuid_wjz_bb, 'Subsidieregeling Kaderwet VWS Subsidies', NOW(), NOW()),
    ('1effff0a-3c97-6ebd-5726-7057e9da8ae2', dep_uuid_wjz_bb, 'Subsidieregeling Kwaliteitsimpuls personeel zieken', NOW(), NOW()),
    ('1effff0a-3c97-6ebe-0c43-1b408a66d1d8', dep_uuid_wjz_bb, 'Subsidieregeling Maatschappelijke Diensttijd', NOW(), NOW()),
    ('1effff0a-3c97-6ebf-381a-bc18d134b702', dep_uuid_wjz_bb, 'Subsidieregeling Opleidingen jeugd ggz', NOW(), NOW()),
    ('1effff0a-3c97-6ec0-63d7-5235dc559d09', dep_uuid_wjz_bb, 'Subsidieregeling Opleidingsmodule Basis Acute Zorg', NOW(), NOW()),
    ('1effff0a-3c97-6ec1-49d2-2a819664c7cf', dep_uuid_wjz_bb, 'Subsidieregeling opschaling curatieve zorg COVID 1', NOW(), NOW()),
    ('1effff0a-3c97-6ec2-0606-50007ab19c07', dep_uuid_wjz_bb, 'Subsidieregeling participatie en emancipatie Sinti', NOW(), NOW()),
    ('1effff0a-3c97-6ec3-9800-854fd6af6758', dep_uuid_wjz_bb, 'Subsidieregeling PG organisaties 2024-2028', NOW(), NOW()),
    ('1effff0a-3c97-6ec4-9d53-443f890a2c92', dep_uuid_wjz_bb, 'Subsidieregeling SOIT', NOW(), NOW()),
    ('1effff0a-3c97-6ec5-639a-f4aceb5fb7cf', dep_uuid_wjz_bb, 'Subsidieregeling specifieke uitkering stimulering', NOW(), NOW()),
    ('1effff0a-3c97-6ec6-637d-d931d75903b5', dep_uuid_wjz_bb, 'Subsidieregeling stageplaatsen zorg', NOW(), NOW()),
    ('1effff0a-3c97-6ec7-9211-695f501061e4', dep_uuid_wjz_bb, 'Subsidieregeling Stageplaatsenzorg II', NOW(), NOW()),
    ('1effff0a-3c97-6ec8-e50e-2efe8eb517c5', dep_uuid_wjz_bb, 'Subsidieregeling tegemoetkoming amateursportorgani', NOW(), NOW()),
    ('1effff0a-3c97-6ec9-ce9d-20e69ab82e44', dep_uuid_wjz_bb, 'Subsidieregeling tegemoetkoming verhuurders sporta', NOW(), NOW()),
    ('1effff0a-3c97-6eca-3262-6618dff31955', dep_uuid_wjz_bb, 'Subsidieregeling topsportwedstrijden inkomstenderv', NOW(), NOW()),
    ('1effff0a-3c9a-65b0-f739-6ec7368080e1', dep_uuid_wjz_bb, 'Subsidieregeling VIPP 5', NOW(), NOW()),
    ('1effff0a-3c9a-65b1-dc0e-76a3b7650e0d', dep_uuid_wjz_bb, 'Subsidieregeling VIPP inzicht PGO', NOW(), NOW()),
    ('1effff0a-3c9a-65b2-0c37-f2df21e3601c', dep_uuid_wjz_bb, 'Tabakswet', NOW(), NOW()),
    ('1effff0a-3c9a-65b3-5476-1e10d94400dc', dep_uuid_wjz_bb, 'Tijdelijke wet maatregelen covid-19', NOW(), NOW()),
    ('1effff0a-3c9a-65b4-cee0-dc9eeae89238', dep_uuid_wjz_bb, 'Topsportwedstrijden en topsportevenementen inkomst', NOW(), NOW()),
    ('1effff0a-3c9a-65b5-ec48-78bdaca8df98', dep_uuid_wjz_bb, 'Vaststelling COVID 19 vaccinatie ziekenhuizen 2022', NOW(), NOW()),
    ('1effff0a-3c9a-65b6-6693-c814cdaee83a', dep_uuid_wjz_bb, 'Warenwet', NOW(), NOW()),
    ('1effff0a-3c9a-65b7-a7ff-a6f955dd13ad', dep_uuid_wjz_bb, 'Wet administratieve rechtspraak BES (WarBES)', NOW(), NOW()),
    ('1effff0a-3c9a-65b8-06cf-6335d1c1d0fa', dep_uuid_wjz_bb, 'Wet BIG Besluit Gezondheidszorgpsycholoog', NOW(), NOW()),
    ('1effff0a-3c9a-65b9-6068-9a8260baaa67', dep_uuid_wjz_bb, 'Wet BIG Bevel', NOW(), NOW()),
    ('1effff0a-3c9a-65ba-6fc1-47314dc4ef48', dep_uuid_wjz_bb, 'Wet BIG Buitenlandse erkenning', NOW(), NOW()),
    ('1effff0a-3c9a-65bb-0235-bb47ceeb6366', dep_uuid_wjz_bb, 'Wet BIG Doorhaling', NOW(), NOW()),
    ('1effff0a-3c9a-65bc-026c-cd517a2d1392', dep_uuid_wjz_bb, 'Wet BIG Herregistratie', NOW(), NOW()),
    ('1effff0a-3c9a-65bd-8549-f6f4d08bde60', dep_uuid_wjz_bb, 'Wet BIG publicatie aantekening', NOW(), NOW()),
    ('1effff0a-3c9a-65be-4dcb-ef8efb1437ee', dep_uuid_wjz_bb, 'Wet BIG Uzi pas', NOW(), NOW()),
    ('1effff0a-3c9a-65bf-bd8e-320802c2122a', dep_uuid_wjz_bb, 'Wet BIG Vakbekwaamheid Diploma', NOW(), NOW()),
    ('1effff0a-3c9a-65c0-975e-cb27362cb1d0', dep_uuid_wjz_bb, 'Wet marktordening gezondheidzorg (wmg)', NOW(), NOW()),
    ('1effff0a-3c9a-65c1-701e-9a99a7b00837', dep_uuid_wjz_bb, 'Wet normering topinkomens', NOW(), NOW()),
    ('1effff0a-3c9a-65c2-7de4-fda036fcb571', dep_uuid_wjz_bb, 'Wet op de medische bijzondere verrichtingen', NOW(), NOW()),
    ('1effff0a-3c9a-65c3-9aac-ffa8032db792', dep_uuid_wjz_bb, 'Wet op de medische hulpmiddelen', NOW(), NOW()),
    ('1effff0a-3c9a-65c4-9cc2-371e3967a0ca', dep_uuid_wjz_bb, 'Wet op het bevolkingsonderzoek', NOW(), NOW()),
    ('1effff0a-3c9a-65c5-3493-fb423b6210e0', dep_uuid_wjz_bb, 'Wet toetreding zorgaanbieders', NOW(), NOW()),
    ('1effff0a-3c9c-6cc0-fead-433ad0e833f1', dep_uuid_wjz_bb, 'WKKGZ', NOW(), NOW()),
    ('1effff0a-3c9c-6cc1-86c0-474fa42ce4b3', dep_uuid_wjz_bb, 'Woo-verzoek', NOW(), NOW()),
    ('1effff0a-3c9c-6cc2-c28e-74bbda2db2aa', dep_uuid_wjz_bb, 'Woo-verzoek (corona)', NOW(), NOW()),
    ('1effff0a-3c9c-6cc3-2adf-415c40c15209', dep_uuid_wjz_bb, 'Wtzi invorderingsbesluit', NOW(), NOW()),
    ('1effff0a-3c9c-6cc4-6536-bcb641605852', dep_uuid_wjz_bb, 'Wtzi last onder dwangsom', NOW(), NOW()),
    ('1effff0a-3c9c-6cc5-f30b-5d840e96c0ae', dep_uuid_wjz_bb, 'Wtzi toelating', NOW(), NOW()),
    ('1effff0a-3c9c-6cc6-17ce-6929da64a3a4', dep_uuid_wjz_bb, 'Zorgverzekeringswet (Zvw)', NOW(), NOW()),
    ('1effff0a-3c9c-6cc7-615d-cf0635db35d3', dep_uuid_wjz_bb, 'Overig', NOW(), NOW());

END $$

