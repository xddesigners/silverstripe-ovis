<?php

namespace XD\Ovis\Schemas;

/**
 * Class PresentationSpecifications
 * @author Bram de Leeuw
 *
 * @property string category
 * @property string brand
 * @property string model
 * @property string version
 * @property string titleSuffix
 * @property string licensePlate
 * @property string chassisNumber
 * @property string description
 * @property boolean new
 * @property boolean damaged
 * @property boolean demo
 * @property boolean classic
 * @property boolean sold
 * @property boolean expected
 * @property boolean stock
 * @property boolean reserved
 * @property boolean outdated
 * @property boolean rental
 * @property boolean exRental
 * @property boolean export
 * @property string memo
 * @property PresentationSpecificationsDates dates
 * @property PresentationSpecificationsMediator mediator
 * @property PresentationSpecificationsWeights weightsMeasures
 * @property PresentationSpecificationsBeds beds
 * @property PresentationSpecificationsWarranty warranty
 * @property PresentationSpecificationPrices prices
 * @property PresentationSpecificationPricesRental pricesRental
 * @property PresentationSpecificationsSpecs specsCaravan
 * @property object marktplaats todo describe {price,title,description}
 * @property PresentationSpecificationsAccessory[] accessories
 */
interface PresentationSpecifications
{
}