<?php

namespace App\Enums;

/**
 * Hauptlebensmittelgruppe = erster Buchstabe des BLS-Codes (z. B. „G480100“ → Gemüse).
 */
enum FoodCategory: string
{
    case Bread = 'B';
    case Cereals = 'C';
    case Pastries = 'D';
    case EggsAndPasta = 'E';
    case Fruit = 'F';
    case Vegetables = 'G';
    case PulsesNutsSeeds = 'H';
    case PotatoesAndMushrooms = 'K';
    case Dairy = 'M';
    case Beverages = 'N';
    case AlcoholicBeverages = 'P';
    case FatsAndOils = 'Q';
    case SeasoningsAndSauces = 'R';
    case Sweets = 'S';
    case Fish = 'T';
    case Meat = 'U';
    case PoultryGameOffal = 'V';
    case Sausages = 'W';
    case Dishes = 'X';
    case MeatAndFishDishes = 'Y';

    public function label(): string
    {
        return match ($this) {
            self::Bread => 'Brot & Brötchen',
            self::Cereals => 'Getreide & Getreideprodukte',
            self::Pastries => 'Kuchen & Gebäck',
            self::EggsAndPasta => 'Eier & Teigwaren',
            self::Fruit => 'Obst',
            self::Vegetables => 'Gemüse',
            self::PulsesNutsSeeds => 'Hülsenfrüchte, Nüsse & Samen',
            self::PotatoesAndMushrooms => 'Kartoffeln & Pilze',
            self::Dairy => 'Milch & Milchprodukte',
            self::Beverages => 'Getränke',
            self::AlcoholicBeverages => 'Alkoholische Getränke',
            self::FatsAndOils => 'Fette & Öle',
            self::SeasoningsAndSauces => 'Gewürze, Saucen & Backzutaten',
            self::Sweets => 'Süßwaren & Zucker',
            self::Fish => 'Fisch & Meeresfrüchte',
            self::Meat => 'Fleisch',
            self::PoultryGameOffal => 'Geflügel, Wild & Innereien',
            self::Sausages => 'Wurst & Fleischwaren',
            self::Dishes => 'Gerichte',
            self::MeatAndFishDishes => 'Gerichte mit Fleisch & Fisch',
        };
    }

    public static function fromBlsCode(?string $code): ?self
    {
        return $code ? self::tryFrom(strtoupper($code[0])) : null;
    }
}
