CREATE TABLE `0_countries` (
    `code` CHAR(2) NOT NULL PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `dialing_code` VARCHAR(10) NULL,
    `nationality` VARCHAR(50) NULL,
    `person` VARCHAR(50) NULL,
    INDEX(`nationality`) USING HASH,
    INDEX(`dialing_code`) USING HASH,
    INDEX(`person`) USING HASH
);

LOCK TABLES `0_countries` WRITE;

INSERT INTO `0_countries` VALUES
('AD', 'Andorra', '+376', 'Andorran', 'an Andorran'),
('AE', 'United Arab Emirates', '+971', 'Emirati', 'a UAE citizen'),
('AF', 'Afghanistan', '+93', 'Afghan', 'an Afghan'),
('AG', 'Antigua and Barbuda', '+268', NULL, NULL),
('AI', 'Anguilla', '+264', NULL, NULL),
('AL', 'Albania', '+355', 'Albanian', 'an Albanian'),
('AM', 'Armenia', '+374', 'Armenian', 'an Armenian'),
('AN', 'Netherlands Antilles', '+599', NULL, NULL),
('AO', 'Angola', '+244', 'Angolan', 'an Angolan'),
('AR', 'Argentina', '+54', 'Argentinian', 'an Argentinian'),
('AT', 'Austria', '+43', 'Austrian', 'an Austrian'),
('AU', 'Australia', '+61', 'Australian', 'an Australian'),
('AW', 'Aruba', '+297', NULL, NULL),
('AZ', 'Azerbaijan', '+994', 'Azerbaijani', 'an Azerbaijani'),
('BA', 'Bosnia and Herzegovina', '+387', 'Bosnian', 'a Bosnian'),
('BB', 'Barbados', '+246', 'Barbadian', 'a Barbadian'),
('BD', 'Bangladesh', '+880', 'Bangladeshi', 'a Bangladeshi'),
('BE', 'Belgium', '+32', 'Belgian', 'a Belgian'),
('BF', 'Burkina Faso', '+226', 'Burkinese', 'a Burkinese'),
('BG', 'Bulgaria', '+359', 'Bulgarian', 'a Bulgarian'),
('BH', 'Bahrain', '+973', 'Bahraini', 'a Bahraini'),
('BI', 'Burundi', '+257', 'Burundian', 'a Burundian'),
('BJ', 'Benin', '+229', 'Beninese', 'a Beninese'),
('BM', 'Bermuda', '+441', 'Bermudian', 'a Bermudian'),
('BN', 'Brunei Darussalam', '+673', NULL, NULL),
('BO', 'Bolivia', '+591', 'Bolivian', 'a Bolivian'),
('BR', 'Brazil', '+55', 'Brazilian', 'a Brazilian'),
('BS', 'Bahamas', '+242', 'Bahamian', 'a Bahamian'),
('BT', 'Bhutan', '+975', 'Bhutanese', 'a Bhutanese'),
('BW', 'Botswana', '+267', 'Botswanan', 'a Tswana'),
('BY', 'Belarus', '+375', 'Belarusian', 'a Belarusian'),
('BZ', 'Belize', '+501', 'Belizean', 'a Belizean'),
('CA', 'Canada', '+1', 'Canadian', 'a Canadian'),
('CC', 'Cocos (Keeling) Islands', '+61', NULL, NULL),
('CD', 'Democratic Republic of the Congo', '+243', NULL, NULL),
('CF', 'Central African Republic', '+236', NULL, NULL),
('CG', 'Congo', '+242', 'Congolese', 'a Congolese'),
('CH', 'Switzerland', '+41', 'Swiss', 'a Swiss'),
('CI', 'Cote D''Ivoire (Ivory Coast)', '+225', NULL, NULL),
('CK', 'Cook Islands', '+682', NULL, NULL),
('CL', 'Chile', '+56', 'Chilean', 'a Chilean'),
('CM', 'Cameroon', '+237', 'Cameroonian', 'a Cameroonian'),
('CN', 'China', '+86', 'Chinese', 'a Chinese'),
('CO', 'Colombia', '+57', 'Colombian', 'a Colombian'),
('CR', 'Costa Rica', '+506', 'Costa Rican', 'a Costa Rican'),
('CU', 'Cuba', '+53', 'Cuban', 'a Cuban'),
('CV', 'Cape Verde', '+238', 'Cape Verdean', 'a Cape Verdean'),
('CX', 'Christmas Island', '+61', NULL, NULL),
('CY', 'Cyprus', '+357', 'Cypriot', 'a Cypriot'),
('CZ', 'Czech Republic', '+420', 'Czech', 'a Czech'),
('DE', 'Germany', '+49', 'German', 'a German'),
('DJ', 'Djibouti', '+253', 'Djiboutian', 'a Djiboutian'),
('DK', 'Denmark', '+45', 'Danish', 'a Dane'),
('DM', 'Dominica', '+767', 'Dominican', 'a Dominican'),
('DO', 'Dominican Republic', '+849', 'Dominican', 'a Dominican'),
('DZ', 'Algeria', '+213', 'Algerian', 'an Algerian'),
('EC', 'Ecuador', '+593', 'Ecuadorean', 'an Ecuadorean'),
('EE', 'Estonia', '+372', 'Estonian', 'an Estonian'),
('EG', 'Egypt', '+20', 'Egyptian', 'an Egyptian'),
('EH', 'Western Sahara', '+212', NULL, NULL),
('ER', 'Eritrea', '+291', 'Eritrean', 'an Eritrean'),
('ES', 'Spain', '+34', 'Spanish', 'a Spaniard'),
('ET', 'Ethiopia', '+251', 'Ethiopian', 'an Ethiopian'),
('FI', 'Finland', '+358', 'Finnish', 'a Finn'),
('FJ', 'Fiji', '+679', 'Fijian', 'a Fijian'),
('FK', 'Falkland Islands (Malvinas)', '+500', NULL, NULL),
('FM', 'Federated States of Micronesia', '+691', NULL, NULL),
('FO', 'Faroe Islands', '+298', NULL, NULL),
('FR', 'France', '+33', 'French', 'a Frenchman'),
('GA', 'Gabon', '+241', 'Gabonese', 'a Gabonese'),
('GB', 'Great Britain (UK)', '+44', 'British', 'a Briton'),
('GD', 'Grenada', '+473', 'Grenadian', 'a Grenadian'),
('GE', 'Georgia', '+995', 'Georgian', 'a Georgian'),
('GF', 'French Guiana', '+594', NULL, NULL),
('GG', 'Guernsey', '+44', NULL, NULL),
('GH', 'Ghana', '+233', 'Ghanaian', 'a Ghanaian'),
('GI', 'Gibraltar', '+350', NULL, NULL),
('GL', 'Greenland', '+299', NULL, NULL),
('GM', 'Gambia', '+220', 'Gambian', 'a Gambian'),
('GN', 'Guinea', '+224', 'Guinean', 'a Guinean'),
('GP', 'Guadeloupe', '+590', NULL, NULL),
('GQ', 'Equatorial Guinea', '+240', NULL, NULL),
('GR', 'Greece', '+30', 'Greek', 'a Greek'),
('GS', 'S. Georgia and S. Sandwich Islands', '+500', NULL, NULL),
('GT', 'Guatemala', '+502', 'Guatemalan', 'a Guatemalan'),
('GW', 'Guinea-Bissau', '+245', NULL, NULL),
('GY', 'Guyana', '+592', 'Guyanese', 'a Guyanese'),
('HK', 'Hong Kong', '+852', NULL, NULL),
('HN', 'Honduras', '+504', 'Honduran', 'a Honduran'),
('HR', 'Croatia (Hrvatska)', '+385', 'Croat', 'a Croat or a Croatian'),
('HT', 'Haiti', '+509', 'Haitian', 'a Haitian'),
('HU', 'Hungary', '+36', 'Hungarian', 'a Hungarian'),
('ID', 'Indonesia', '+62', 'Indonesian', 'an Indonesian'),
('IE', 'Ireland', '+353', 'Ireland', 'an Irishman'),
('IL', 'Israel', '+972', 'Israeli', 'an Israeli'),
('IN', 'India', '+91', 'Indian', 'an Indian'),
('IQ', 'Iraq', '+964', 'Iraqi', 'an Iraqi'),
('IR', 'Iran', '+98', 'Iranian', 'an Iranian'),
('IS', 'Iceland', '+354', 'Icelandic', 'an Icelander'),
('IT', 'Italy', '+39', 'Italian', 'an Italian'),
('JM', 'Jamaica', '+876', 'Jamaican', 'a Jamaican'),
('JO', 'Jordan', '+962', 'Jordanian', 'a Jordanian'),
('JP', 'Japan', '+81', 'Japanese', 'a Japanese'),
('KE', 'Kenya', '+254', 'Kenyan', 'a Kenyan'),
('KG', 'Kyrgyzstan', '+996', NULL, NULL),
('KH', 'Cambodia', '+855', 'Cambodian', 'a Cambodian'),
('KI', 'Kiribati', '+686', NULL, NULL),
('KM', 'Comoros', '+269', 'Comorian', 'a Comorian'),
('KN', 'Saint Kitts and Nevis', '+869', NULL, NULL),
('KP', 'Korea (North)', '+850', 'North Korean', 'a North Korean'),
('KR', 'Korea (South)', '+82', 'South Korean', 'a South Korean'),
('KW', 'Kuwait', '+965', 'Kuwaiti', 'a Kuwaiti'),
('KY', 'Cayman Islands', '+345', NULL, NULL),
('KZ', 'Kazakhstan', '+7', 'Kazakh', 'a Kazakh'),
('LA', 'Laos', '+856', NULL, NULL),
('LB', 'Lebanon', '+961', 'Lebanese', 'a Lebanese'),
('LC', 'Saint Lucia', '+758', NULL, NULL),
('LI', 'Liechtenstein', '+423', NULL, NULL),
('LK', 'Sri Lanka', '+94', 'Sri Lankan', 'a Sri Lankan'),
('LR', 'Liberia', '+231', 'Liberian', 'a Liberian'),
('LS', 'Lesotho', '+266', NULL, NULL),
('LT', 'Lithuania', '+370', 'Lithuanian', 'a Lithuanian'),
('LU', 'Luxembourg', '+352', 'LUXEMBOURG', 'a Luxembourger'),
('LV', 'Latvia', '+371', 'Latvian', 'a Latvian'),
('LY', 'Libya', '+218', 'Libyan', 'a Libyan'),
('MA', 'Morocco', '+212', 'Moroccan', 'a Moroccan'),
('MC', 'Monaco', '+377', 'Monacan', 'a Monégasque'),
('MD', 'Moldova', '+373', NULL, NULL),
('MG', 'Madagascar', '+261', 'Madagascan', 'a Malagasy'),
('MH', 'Marshall Islands', '+692', NULL, NULL),
('MK', 'Macedonia', '+389', NULL, NULL),
('ML', 'Mali', '+223', 'Malian', 'a Malian'),
('MM', 'Myanmar', '+95', NULL, NULL),
('MN', 'Mongolia', '+976', 'Mongolian', 'a Mongolian'),
('MO', 'Macao', '+853', NULL, NULL),
('MP', 'Northern Mariana Islands', '+670', NULL, NULL),
('MQ', 'Martinique', '+596', NULL, NULL),
('MR', 'Mauritania', '+222', 'Mauritanian', 'a Mauritanian'),
('MS', 'Montserrat', '+664', NULL, NULL),
('MT', 'Malta', '+356', 'Maltese', 'a Maltese'),
('MU', 'Mauritius', '+230', 'Mauritian', 'a Mauritian'),
('MV', 'Maldives', '+960', 'Maldivian', 'a Maldivian'),
('MW', 'Malawi', '+265', 'Malawian', 'a Malawian'),
('MX', 'Mexico', '+52', 'Mexican', 'a Mexican'),
('MY', 'Malaysia', '+60', 'Malaysian', 'a Malaysian'),
('MZ', 'Mozambique', '+258', 'Mozambican', 'a Mozambican'),
('NA', 'Namibia', '+264', 'Namibian', 'a Namibian'),
('NC', 'New Caledonia', '+687', NULL, NULL),
('NE', 'Niger', '+227', 'Nigerien', 'a Nigerien'),
('NF', 'Norfolk Island', '+672', NULL, NULL),
('NG', 'Nigeria', '+234', 'Nigerian', 'a Nigerian'),
('NI', 'Nicaragua', '+505', 'Nicaraguan', 'a Nicaraguan'),
('NL', 'Netherlands', '+31', 'Dutch', '"a Dutchman,"'),
('NO', 'Norway', '+47', 'Norwegian', 'a Norwegian'),
('NP', 'Nepal', '+977', 'Nepalese', 'a Nepalese'),
('NR', 'Nauru', '+674', NULL, NULL),
('NU', 'Niue', '+683', NULL, NULL),
('NZ', 'New Zealand (Aotearoa)', '+64', 'New Zealand', 'a New Zealander'),
('OM', 'Oman', '+968', 'Omani', 'an Omani'),
('PA', 'Panama', '+507', 'Panamanian', 'a Panamanian'),
('PE', 'Peru', '+51', 'Peruvian', 'a Peruvian'),
('PF', 'French Polynesia', '+689', 'Polynesian', 'a French Polynesian'),
('PG', 'Papua New Guinea', '+675', 'Guinean', 'a Papua New Guinean'),
('PH', 'Philippines', '+63', 'Philippine', 'a Filipino'),
('PK', 'Pakistan', '+92', 'Pakistani', 'a Pakistani'),
('PL', 'Poland', '+48', 'Polish', 'a Pole'),
('PM', 'Saint Pierre and Miquelon', '+508', NULL, NULL),
('PN', 'Pitcairn', '+64', NULL, NULL),
('PS', 'Palestinian Territory', '+970', 'Palestinian', 'a Palestinian'),
('PT', 'Portugal', '+351', 'Portuguese', 'a Portuguese'),
('PW', 'Palau', '+680', NULL, NULL),
('PY', 'Paraguay', '+595', 'Paraguayan', 'a Paraguayan'),
('QA', 'Qatar', '+974', 'Qatari', 'a Qatari'),
('RE', 'Reunion', '+262', NULL, NULL),
('RO', 'Romania', '+40', 'Romanian', 'a Romanian'),
('RU', 'Russian Federation', '+7', NULL, NULL),
('RW', 'Rwanda', '+250', 'Rwandan', 'a Rwandan'),
('SA', 'Saudi Arabia', '+966', 'Saudi Arabian', 'a Saudi Arabian'),
('SB', 'Solomon Islands', '+677', 'Slomoni', 'a Solomon Islander'),
('SC', 'Seychelles', '+248', NULL, NULL),
('SD', 'Sudan', '+249', 'Sudanese', 'a Sudanese'),
('SE', 'Sweden', '+46', 'Swedish', 'a Swede'),
('SG', 'Singapore', '+65', 'Singaporean', 'a Singaporean'),
('SH', 'Saint Helena', '+290', NULL, NULL),
('SI', 'Slovenia', '+386', 'Slovenian', 'a Slovene'),
('SJ', 'Svalbard and Jan Mayen', '+47', NULL, NULL),
('SK', 'Slovakia', '+421', 'Slovak', 'a Slovak'),
('SL', 'Sierra Leone', '+232', 'Sierra Leonian', 'a Sierra Leonian'),
('SM', 'San Marino', '+378', NULL, NULL),
('SN', 'Senegal', '+221', 'Senegalese', 'a Senegalese'),
('SO', 'Somalia', '+252', 'Somali', 'a Somali'),
('SR', 'Suriname', '+597', 'Surinamese', 'a Surinamer'),
('ST', 'Sao Tome and Principe', '+239', NULL, NULL),
('SV', 'El Salvador', '+503', 'Salvadorean', 'a Salvadorean'),
('SY', 'Syria', '+963', 'Syrian', 'a Syrian'),
('SZ', 'Swaziland', '+268', 'Swazi', 'a Swazi'),
('TC', 'Turks and Caicos Islands', '+649', NULL, NULL),
('TD', 'Chad', '+235', 'Chadian', 'a Chadian'),
('TF', 'French Southern Territories', '+262', NULL, NULL),
('TG', 'Togo', '+228', 'Togolese', 'a Togolese'),
('TH', 'Thailand', '+66', 'Thai', 'a Thai'),
('TJ', 'Tajikistan', '+992', 'Tajik', 'a Tajik'),
('TK', 'Tokelau', '+690', NULL, NULL),
('TM', 'Turkmenistan', '+993', 'Turkoman', 'a Turkmen'),
('TN', 'Tunisia', '+216', 'Tunisian', 'a Tunisian'),
('TO', 'Tonga', '+676', NULL, NULL),
('TR', 'Turkey', '+90', 'Turkish', 'a Turk'),
('TT', 'Trinidad and Tobago', '+868', 'Trinidadian', 'a Trinidadian'),
('TV', 'Tuvalu', '+688', 'Tuvaluan', 'a Tuvaluan'),
('TW', 'Taiwan', '+886', 'Taiwanese', 'a Taiwanese'),
('TZ', 'Tanzania', '+255', NULL, NULL),
('UA', 'Ukraine', '+380', 'Ukrainian', 'a Ukrainian'),
('UG', 'Uganda', '+256', 'Ugandan', 'a Ugandan'),
('US', 'United States of America', '+1', 'American', 'a US citizen'),
('UY', 'Uruguay', '+598', 'Uruguayan', 'a Uruguayan'),
('UZ', 'Uzbekistan', '+998', 'Uzbek', 'an Uzbek'),
('VC', 'Saint Vincent and the Grenadines', '+784', NULL, NULL),
('VE', 'Venezuela', '+58', 'Venezuelan', 'a Venezuelan'),
('VG', 'Virgin Islands (British)', '+284', NULL, NULL),
('VI', 'Virgin Islands (U.S.)', '+340', NULL, NULL),
('VN', 'Viet Nam', '+84', 'Vietnamese', 'a Vietnamese'),
('VU', 'Vanuatu', '+678', 'Vanuatuan', 'a Vanuatuan'),
('WF', 'Wallis and Futuna', '+681', NULL, NULL),
('WS', 'Samoa', '+685', NULL, NULL),
('YE', 'Yemen', '+967', 'Yemeni', 'a Yemeni'),
('YT', 'Mayotte', '+262', NULL, NULL),
('ZA', 'South Africa', '+27', 'South African', 'a South African'),
('ZM', 'Zambia', '+260', 'Zambian', 'a Zambian'),
('ZR', 'Zaire (former)', '+243', NULL, NULL),
('ZW', 'Zimbabwe', '+263', NULL, NULL);
UNLOCK TABLES;