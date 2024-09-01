<?php


namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self consulting()
 * @method static self ecommerce()
 * @method static self finance()
 * @method static self gaming()
 * @method static self hardware()
 * @method static self infrastructure()
 * @method static self marketing()
 * @method static self robotics()
 * @method static self security()
 * @method static self software()
 * @method static self telecom()
 * @method static self vfx()
 * @method static self advertisement()
 * @method static self film()
 * @method static self accounting()
 * @method static self aviation()
 * @method static self animation()
 * @method static self architecture()
 * @method static self arts()
 * @method static self automotive()
 * @method static self banking()
 * @method static self biotechnology()
 * @method static self broadcast_media()
 * @method static self business_supplies()
 * @method static self chemicals()
 * @method static self civil_engineering()
 * @method static self commercial_real_estate()
 * @method static self construction()
 * @method static self cosmetics()
 * @method static self dairy()
 * @method static self defense()
 * @method static self design()
 * @method static self elearning()
 * @method static self electronic_manufacturing()
 * @method static self entertainment()
 * @method static self events_services()
 * @method static self facilities_services()
 * @method static self farming()
 * @method static self fashion()
 * @method static self fishery()
 * @method static self food_production()
 * @method static self food_beverages()
 * @method static self fundraising()
 * @method static self furniture()
 * @method static self gambling()
 * @method static self graphic_design()
 * @method static self health()
 * @method static self health_care()
 * @method static self hospitality()
 * @method static self human_resources()
 * @method static self industrial_automation()
 * @method static self insurance()
 * @method static self international_affairs()
 * @method static self international_trade()
 * @method static self investment_banking()
 * @method static self investment_management()
 * @method static self judiciary()
 * @method static self law_enforcement()
 * @method static self law_practise()
 * @method static self legal_services()
 * @method static self legislative_office()
 * @method static self leisure()
 * @method static self library()
 * @method static self logistics()
 * @method static self luxury_goods()
 * @method static self machinery()
 * @method static self management_consulting()
 * @method static self maritime()
 * @method static self mechanical_engineering()
 * @method static self medical_equipment()
 * @method static self medical_practice()
 * @method static self mental_health_care()
 * @method static self military_industry()
 * @method static self mining()
 * @method static self museums()
 * @method static self music()
 * @method static self nanotechnology()
 * @method static self non_profit()
 * @method static self oil_industry()
 * @method static self online_publishing()
 * @method static self outsourcing()
 * @method static self packaging()
 * @method static self pharmaceuticals()
 * @method static self photography()
 * @method static self plastics()
 * @method static self politics()
 * @method static self printing()
 * @method static self professional_training()
 * @method static self public_relations()
 * @method static self public_safety()
 * @method static self publishing()
 * @method static self railroad()
 * @method static self ranching()
 * @method static self recruiting()
 * @method static self religious()
 * @method static self research()
 * @method static self restaurants()
 * @method static self retail()
 * @method static self shipbuilding()
 * @method static self social()
 * @method static self sports()
 * @method static self supermarkets()
 * @method static self textiles()
 * @method static self tobacco()
 * @method static self translation()
 * @method static self transportation()
 * @method static self utilities()
 * @method static self veterinary()
 * @method static self warehousing()
 * @method static self writing()
 *
 * @method static bool isConsulting(int|string $value = null)
 * @method static bool isEcommerce(int|string $value = null)
 * @method static bool isFinance(int|string $value = null)
 * @method static bool isGaming(int|string $value = null)
 * @method static bool isHardware(int|string $value = null)
 * @method static bool isInfrastructure(int|string $value = null)
 * @method static bool isMarketing(int|string $value = null)
 * @method static bool isRobotics(int|string $value = null)
 * @method static bool isSecurity(int|string $value = null)
 * @method static bool isSoftware(int|string $value = null)
 * @method static bool isTelecom(int|string $value = null)
 * @method static bool isVFX(int|string $value = null)
 * @method static bool isAdvertisement(int|string $value = null)
 * @method static bool isFilm(int|string $value = null)
 * @method static bool isAccounting(int|string $value = null)
 * @method static bool isAviation(int|string $value = null)
 * @method static bool isAnimation(int|string $value = null)
 * @method static bool isArchitecture(int|string $value = null)
 * @method static bool isArts(int|string $value = null)
 * @method static bool isAutomotive(int|string $value = null)
 * @method static bool isBanking(int|string $value = null)
 * @method static bool isBiotechnology(int|string $value = null)
 * @method static bool isBroadcast_media(int|string $value = null)
 * @method static bool isBusiness_supplies(int|string $value = null)
 * @method static bool isChemicals(int|string $value = null)
 * @method static bool isCivil_engineering(int|string $value = null)
 * @method static bool isCommercial_real_estate(int|string $value = null)
 * @method static bool isConstruction(int|string $value = null)
 * @method static bool isCosmetics(int|string $value = null)
 * @method static bool isDairy(int|string $value = null)
 * @method static bool isDefense(int|string $value = null)
 * @method static bool isDesign(int|string $value = null)
 * @method static bool isElearning(int|string $value = null)
 * @method static bool isElectronic_manufacturing(int|string $value = null)
 * @method static bool isEntertainment(int|string $value = null)
 * @method static bool isEvents_services(int|string $value = null)
 * @method static bool isFacilities_services(int|string $value = null)
 * @method static bool isFarming(int|string $value = null)
 * @method static bool isFashion(int|string $value = null)
 * @method static bool isFishery(int|string $value = null)
 * @method static bool isFood_production(int|string $value = null)
 * @method static bool isFood_beverages(int|string $value = null)
 * @method static bool isFundraising(int|string $value = null)
 * @method static bool isFurniture(int|string $value = null)
 * @method static bool isGambling(int|string $value = null)
 * @method static bool isGraphic_design(int|string $value = null)
 * @method static bool isHealth(int|string $value = null)
 * @method static bool isHealth_care(int|string $value = null)
 * @method static bool isHospitality(int|string $value = null)
 * @method static bool isHuman_resources(int|string $value = null)
 * @method static bool isIndustrial_automation(int|string $value = null)
 * @method static bool isInsurance(int|string $value = null)
 * @method static bool isInternational_affairs(int|string $value = null)
 * @method static bool isInternational_trade(int|string $value = null)
 * @method static bool isInvestment_banking(int|string $value = null)
 * @method static bool isInvestment_management(int|string $value = null)
 * @method static bool isJudiciary(int|string $value = null)
 * @method static bool isLaw_enforcement(int|string $value = null)
 * @method static bool isLaw_practice(int|string $value = null)
 * @method static bool isLegal_services(int|string $value = null)
 * @method static bool isLegislative_office(int|string $value = null)
 * @method static bool isLeisure(int|string $value = null)
 * @method static bool isLibrary(int|string $value = null)
 * @method static bool isLogistics(int|string $value = null)
 * @method static bool isLuxury_goods(int|string $value = null)
 * @method static bool isMachinery(int|string $value = null)
 * @method static bool isManagment_consulting(int|string $value = null)
 * @method static bool isMaritime(int|string $value = null)
 * @method static bool isMechanical_engineering(int|string $value = null)
 * @method static bool isMedical_equipment(int|string $value = null)
 * @method static bool isMedical_practice(int|string $value = null)
 * @method static bool isMental_health_care(int|string $value = null)
 * @method static bool isMilitary_industry(int|string $value = null)
 * @method static bool isMining(int|string $value = null)
 * @method static bool isMuseums(int|string $value = null)
 * @method static bool isMusic(int|string $value = null)
 * @method static bool isNanotechnology(int|string $value = null)
 * @method static bool isNon_profit(int|string $value = null)
 * @method static bool isOil_industry(int|string $value = null)
 * @method static bool isOnline_publishing(int|string $value = null)
 * @method static bool isOutsourcing(int|string $value = null)
 * @method static bool isPackaging(int|string $value = null)
 * @method static bool isPharmaceuticals(int|string $value = null)
 * @method static bool isPhotography(int|string $value = null)
 * @method static bool isPlastics(int|string $value = null)
 * @method static bool isPolitics(int|string $value = null)
 * @method static bool isPrinting(int|string $value = null)
 * @method static bool isProfessional_training(int|string $value = null)
 * @method static bool isPublic_relations(int|string $value = null)
 * @method static bool isPublic_safety(int|string $value = null)
 * @method static bool isPublishing(int|string $value = null)
 * @method static bool isRailroad(int|string $value = null)
 * @method static bool isRanching(int|string $value = null)
 * @method static bool isRecruiting(int|string $value = null)
 * @method static bool isReligious(int|string $value = null)
 * @method static bool isResearch(int|string $value = null)
 * @method static bool isRestaurants(int|string $value = null)
 * @method static bool isRetail(int|string $value = null)
 * @method static bool isShipbuilding(int|string $value = null)
 * @method static bool isSocial(int|string $value = null)
 * @method static bool isSports(int|string $value = null)
 * @method static bool isSupermarkets(int|string $value = null)
 * @method static bool isTextiles(int|string $value = null)
 * @method static bool isTobacco(int|string $value = null)
 * @method static bool isTranslation(int|string $value = null)
 * @method static bool isTransportation(int|string $value = null)
 * @method static bool isUtilities(int|string $value = null)
 * @method static bool isVeterinary(int|string $value = null)
 * @method static bool isWarehousing(int|string $value = null)
 * @method static bool isWriting(int|string $value = null)
 */
final class IndustryType extends Enum
{
    const MAP_INDEX = [
        'consulting' => 0,
        'ecommerce' => 1,
        'finance' => 2,
        'gaming' => 3,
        'hardware' => 4,
        'infrastructure' => 5,
        'marketing' => 6,
        'robotics' => 7,
        'security' => 8,
        'software' => 9,
        'telecom' => 10,
        'vfx' => 11,
        'advertisement' => 12,
        'film' => 13,
        'accounting' => 14,
        'aviation' => 15,
        'animation' => 16,
        'architecture' => 17,
        'arts' => 18,
        'automotive' => 19,
        'banking' => 20,
        'biotechnology' => 21,
        'broadcast_media' => 22,
        'business_supplies' => 23,
        'chemicals' => 24,
        'civil_engineering' => 25,
        'commercial_real_estate' => 26,
        'construction' => 27,
        'cosmetics' => 28,
        'dairy' => 29,
        'defense' => 30,
        'design' => 31,
        'elearning' => 32,
        'electronic_manufacturing' => 33,
        'entertainment' => 34,
        'events_services' => 35,
        'facilities_services' => 36,
        'farming' => 37,
        'fashion' => 38,
        'fishery' => 39,
        'food_production' => 40,
        'food_beverages' => 41,
        'fundraising' => 42,
        'furniture' => 43,
        'gambling' => 44,
        'graphic_design' => 45,
        'health' => 46,
        'health_care' => 47,
        'hospitality' => 48,
        'human_resources' => 49,
        'industrial_information' => 50,
        'insurance' => 51,
        'international_affairs' => 52,
        'international_trade' => 53,
        'investment_banking' => 54,
        'investment_management' => 55,
        'judiciary' => 56,
        'law_enforcement' => 57,
        'law_practice' => 58,
        'legal services' => 59,
        'legislative_office' => 60,
        'leisure' => 61,
        'library' => 62,
        'logistics' => 63,
        'luxury_goods' => 64,
        'machinery' => 65,
        'management_consulting' => 66,
        'maritime' => 67,
        'mechanical_engineering' => 68,
        'medical_equipment' => 69,
        'medical_practice' => 70,
        'mental_health_care' => 71,
        'military_industry' => 72,
        'mining' => 73,
        'museums' => 74,
        'music' => 75,
        'nanotechnology' => 76,
        'non_profit' => 77,
        'oil_industry' => 78,
        'online_publishing' => 79,
        'outsourcing' => 80,
        'packaging' => 81,
        'pharmaceuticals' => 82,
        'photography' => 83,
        'plastics' => 84,
        'politics' => 85,
        'printing' => 86,
        'professional_training' => 87,
        'public_relations' => 88,
        'public_safety' => 89,
        'publishing' => 90,
        'railroad' => 91,
        'ranching' => 92,
        'recruiting' => 93,
        'religious' => 94,
        'research' => 95,
        'restaurants' => 96,
        'retail' => 97,
        'shipbuilding' => 98,
        'social' => 99,
        'sports' => 100,
        'supermarkets' => 101,
        'textiles' => 102,
        'tobacco' => 103,
        'translation' => 104,
        'transportation' => 105,
        'utilities' => 106,
        'veterinary' => 107,
        'warehousing' => 108,
        'writing' => 109,
    ];

    const MAP_VALUE = [
        'consulting' => 'Consulting',
        'ecommerce' => 'E-commerce',
        'finance' => 'Finance',
        'gaming' => 'Gaming',
        'hardware' => 'Hardware',
        'infrastructure' => 'Infrastructure',
        'marketing' => 'Marketing',
        'robotics' => 'Robotics',
        'security' => 'Security',
        'software' => 'Software',
        'telecom' => 'Telecom',
        'vfx' => 'VFX',
        'advertisement' => 'Advertisement',
        'film' => 'Film & Television',
        'accounting' => 'Accounting',
        'aviation' => 'Aviation',
        'animation' => 'Animation',
        'architecture' => 'Architecture',
        'arts' => 'Arts',
        'automotive' => 'Automotive',
        'banking' => 'Banking',
        'biotechnology' => 'Biotechnology',
        'broadcast_media' => 'Broadcast Media',
        'business_supplies' => 'Business Supplies',
        'civil_engineering' => 'Civil Engineering',
        'commercial_real_estate' => 'Commercial Real Estate',
        'construction' => 'Construction',
        'cosmetics' => 'Cosmetics',
        'dairy' => 'Dairy',
        'defense' => 'Defense',
        'design' => 'Design',
        'elearning' => 'E-learning',
        'electronic_manufacturing' => 'Electronic Manufacturing',
        'entertainment' => 'Entertainment',
        'events_services' => 'Events Services',
        'facilities_services' => 'Facilities Services',
        'farming' => 'Farming',
        'fashion' => 'Fashion',
        'fishery' => 'Fishery',
        'food_production' => 'Food Production',
        'food_beverages' => 'Food Beverages',
        'fundraising' => 'Fundraising',
        'furniture' => 'Furniture',
        'gambling' => 'Gambling',
        'graphic_design' => 'Graphic Design',
        'health' => 'Health & Fitness',
        'health_care' => 'Health Care',
        'hospitality' => 'Hospitality',
        'human_resources' => 'Human Resources',
        'industrial_information' => 'Industrial Information',
        'insurance' => 'Insurance',
        'international_affairs' => 'International Affairs',
        'international_trade' => 'International Trade',
        'investment_banking' => 'Investment Banking',
        'investment_management' => 'Investment Management',
        'judiciary' => 'Judiciary',
        'law_enforcement' => 'Law Enforcement',
        'law_practice' => 'Law Practice',
        'legal services' => 'Legal Services',
        'legislative_office' => 'Legislative Office',
        'leisure' => 'Leisure & Travel',
        'library' => 'Library',
        'logistics' => 'Logistics',
        'luxury_goods' => 'Luxury Goods',
        'machinery' => 'Machinery',
        'management_consulting' => 'Management Consulting',
        'maritime' => 'Maritime',
        'mechanical_engineering' => 'Mechanical Engineering',
        'medical_equipment' => 'Medical Equipment',
        'medical_practice' => 'Medical Practice',
        'mental_health_care' => 'Mental Health Care',
        'military_industry' => 'Military Industry',
        'mining' => 'Mining & Metals',
        'museums' => 'Museums',
        'music' => 'Music',
        'nanotechnology' => 'Nanotechnology',
        'non_profit' => 'Non-profit',
        'oil_industry' => 'oil Industry',
        'online_publishing' => 'Online Publishing',
        'outsourcing' => 'Outsourcing',
        'packaging' => 'Packaging',
        'pharmaceuticals' => 'Pharmaceuticals',
        'photography' => 'Photography',
        'plastics' => 'Plastics',
        'politics' => 'Politics',
        'printing' => 'Printing',
        'professional_training' => 'Professional Training',
        'public_relations' => 'Public Relations',
        'public_safety' => 'Public Safety',
        'publishing' => 'Publishing',
        'railroad' => 'Railroad Manufacture',
        'ranching' => 'Ranching',
        'recruiting' => 'Recruiting',
        'religious' => 'Religious',
        'research' => 'Research',
        'restaurants' => 'Restaurants',
        'retail' => 'Retail',
        'shipbuilding' => 'Shipbuilding',
        'social' => 'Social Organization',
        'sports' => 'Sports',
        'supermarkets' => 'Supermarkets',
        'textiles' => 'Textiles',
        'tobacco' => 'Tobacco',
        'translation' => 'Translation',
        'transportation' => 'Transportation',
        'utilities' => 'Utilities',
        'veterinary' => 'Veterinary',
        'warehousing' => 'Warehousing',
        'writing' => 'Writing & Editing',
    ];
}
