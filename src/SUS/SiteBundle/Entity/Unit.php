<?php

namespace SUS\SiteBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * Units
 *
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 * @ORM\Table(name="units")
 * @ORM\Entity
 */
class Unit extends MMSyncableEntity
{
    /**
     * @var integer
     *
     * @ORM\Column(name="unit_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $unitId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="special_name", type="string", length=255, nullable=true)
     */
    private $specialName;

    /**
     * @var string
     *
     * @ORM\Column(name="street_address", type="string", length=255, nullable=true)
     */
    private $streetAddress;

    /**
     * @var integer
     *
     * @ORM\Column(name="postal_code", type="integer", nullable=true)
     */
    private $postalCode;

    /**
     * @var string
     *
     * @ORM\Column(name="fax_number", type="string", length=255, nullable=true)
     */
    private $faxNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="phone_number", type="string", length=255, nullable=true)
     */
    private $phoneNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_unit_update", type="datetime", nullable=true)
     */
    private $lastUnitUpdate;

    /**
     * @var string
     *
     * @ORM\Column(name="tax_number", type="string", length=255, nullable=true)
     */
    private $taxNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="comments", type="string", length=255, nullable=true)
     */
    private $comments;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_update", type="datetime", nullable=true)
     */
    private $lastUpdate;

    /**
     * @var string
     *
     * @ORM\Column(name="positioning", type="string", length=255, nullable=true)
     */
    private $positioning;

    /**
     * @var string
     *
     * @ORM\Column(name="website", type="string", length=255, nullable=true)
     */
    private $website;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="foundation_date", type="date", nullable=true)
     */
    private $foundationDate;

    /**
     * @var TaxOffices
     *
     * @ORM\ManyToOne(targetEntity="TaxOffices")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tax_office_id", referencedColumnName="tax_office_id")
     * })
     */
    private $taxOffice;

    /**
     * @var EduAdmins
     *
     * @ORM\ManyToOne(targetEntity="EduAdmins")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="edu_admin_id", referencedColumnName="edu_admin_id")
     * })
     */
    private $eduAdmin;

    /**
     * @var RegionEduAdmins
     *
     * @ORM\ManyToOne(targetEntity="RegionEduAdmins")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="region_edu_admin_id", referencedColumnName="region_edu_admin_id")
     * })
     */
    private $regionEduAdmin;

    /**
     * @var ImplementationEntities
     *
     * @ORM\ManyToOne(targetEntity="ImplementationEntities")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="implementation_entity_id", referencedColumnName="implementation_entity_id")
     * })
     */
    private $implementationEntity;

    /**
     * @var UnitTypes
     *
     * @ORM\ManyToOne(targetEntity="UnitTypes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="unit_type_id", referencedColumnName="unit_type_id")
     * })
     */
    private $unitType;

    /**
     * @var Prefectures
     *
     * @ORM\ManyToOne(targetEntity="Prefectures")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="prefecture_id", referencedColumnName="prefecture_id")
     * })
     */
    private $prefecture;

    /**
     * @var States
     *
     * @ORM\ManyToOne(targetEntity="States")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="state_id", referencedColumnName="state_id")
     * })
     */
    private $state;

    /**
     * @var UnitCategory
     *
     * @ORM\ManyToOne(targetEntity="UnitCategory")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="category_id", referencedColumnName="category_id")
     * })
     */
    private $category;

    /**
     * @var Municipalities
     *
     * @ORM\ManyToOne(targetEntity="Municipalities")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="municipality_id", referencedColumnName="municipality_id")
     * })
     */
    private $municipality;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $deletedAt;

    public function getUnitId() {
        return $this->unitId;
    }

    public function setUnitId($unitId) {
        $this->unitId = $unitId;
    }

    public function getMmId() {
        return $this->mmId;
    }

    public function setMmId($mmId) {
        $this->mmId = $mmId;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getSpecialName() {
        return $this->specialName;
    }

    public function setSpecialName($specialName) {
        $this->specialName = $specialName;
    }

    public function getStreetAddress() {
        return $this->streetAddress;
    }

    public function setStreetAddress($streetAddress) {
        $this->streetAddress = $streetAddress;
    }

    public function getPostalCode() {
        return $this->postalCode;
    }

    public function setPostalCode($postalCode) {
        $this->postalCode = $postalCode;
    }

    public function getFaxNumber() {
        return $this->faxNumber;
    }

    public function setFaxNumber($faxNumber) {
        $this->faxNumber = $faxNumber;
    }

    public function getPhoneNumber() {
        return $this->phoneNumber;
    }

    public function setPhoneNumber($phoneNumber) {
        $this->phoneNumber = $phoneNumber;
    }

    public function getEmail() {
        return $this->email;
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function getLastUnitUpdate() {
        return $this->lastUnitUpdate;
    }

    public function setLastUnitUpdate(\DateTime $lastUnitUpdate) {
        $this->lastUnitUpdate = $lastUnitUpdate;
    }

    public function getTaxNumber() {
        return $this->taxNumber;
    }

    public function setTaxNumber($taxNumber) {
        $this->taxNumber = $taxNumber;
    }

    public function getComments() {
        return $this->comments;
    }

    public function setComments($comments) {
        $this->comments = $comments;
    }

    public function getLastUpdate() {
        return $this->lastUpdate;
    }

    public function setLastUpdate(\DateTime $lastUpdate) {
        $this->lastUpdate = $lastUpdate;
    }

    public function getPositioning() {
        return $this->positioning;
    }

    public function setPositioning($positioning) {
        $this->positioning = $positioning;
    }

    public function getWebsite() {
        return $this->website;
    }

    public function setWebsite($website) {
        $this->website = $website;
    }

    public function getFoundationDate() {
        return $this->foundationDate;
    }

    public function setFoundationDate(\DateTime $foundationDate=null) {
        $this->foundationDate = $foundationDate;
    }

    public function getTaxOffice() {
        return $this->taxOffice;
    }

    public function setTaxOffice(TaxOffices $taxOffice) {
        $this->taxOffice = $taxOffice;
    }

    public function getEduAdmin() {
        return $this->eduAdmin;
    }

    public function setEduAdmin(EduAdmins $eduAdmin) {
        $this->eduAdmin = $eduAdmin;
    }

    public function getRegionEduAdmin() {
        return $this->regionEduAdmin;
    }

    public function setRegionEduAdmin(RegionEduAdmins $regionEduAdmin) {
        $this->regionEduAdmin = $regionEduAdmin;
    }

    public function getUnitType() {
        return $this->unitType;
    }

    public function setUnitType(UnitTypes $unitType) {
        $this->unitType = $unitType;
    }

    public function getPrefecture() {
        return $this->prefecture;
    }

    public function setPrefecture(Prefectures $prefecture) {
        $this->prefecture = $prefecture;
    }

    public function getState() {
        return $this->state;
    }

    public function setState(States $state) {
        $this->state = $state;
    }

    public function getCategory() {
        return $this->category;
    }

    public function setCategory(UnitCategory $category) {
        $this->category = $category;
    }

    public function getMunicipality() {
        return $this->municipality;
    }

    public function setMunicipality(Municipalities $municipality) {
        $this->municipality = $municipality;
    }

    public function getDeletedAt() {
        return $this->deletedAt;
    }

    public function setDeletedAt($deletedAt) {
        $this->deletedAt = $deletedAt;
    }

    public function isActive() {
        return !isset($this->deletedAt);
    }

    public function __toString() {
        return $this->getName();
    }
}
