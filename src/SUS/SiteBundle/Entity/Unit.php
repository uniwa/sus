<?php

namespace SUS\SiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
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
    use TimestampableEntity;

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
     * @ORM\Column(name="registry_no", type="string", length=11, nullable=true, unique=true)
     */
    private $registryNo;

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
     * @var \Year
     *
     * @ORM\Column(name="foundation_date", columnDefinition="YEAR", nullable=true)
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
     * @var Workers
     *
     * @ORM\OneToOne(targetEntity="Workers", mappedBy="unit")
     */
    private $manager;

    /**
     * @var Workers
     *
     * @ORM\ManyToMany(targetEntity="Workers", inversedBy="responsibleUnits", cascade={"persist"})
     * @ORM\JoinTable(name="workers_responsibles",
     *      joinColumns={@ORM\JoinColumn(name="unit_id", referencedColumnName="unit_id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="worker_id", referencedColumnName="worker_id", onDelete="CASCADE")}
     *      )
     */
    private $responsibles;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $deletedAt;

    public function __construct() {
        $this->responsibles = new ArrayCollection();
    }

    public function getId() {
        return $this->getUnitId();
    }

    public function getUnitId() {
        return $this->unitId;
    }

    public function setUnitId($unitId) {
        $this->unitId = $unitId;
    }

    public function getRegistryNo() {
        return $this->registryNo;
    }

    public function setRegistryNo($registryNo) {
        $this->registryNo = $registryNo;
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

    public function setFoundationDate($foundationDate=null) {
        $this->foundationDate = $foundationDate;
    }

    public function getTaxOffice() {
        return $this->taxOffice;
    }

    public function setTaxOffice(TaxOffices $taxOffice=null) {
        $this->taxOffice = $taxOffice;
    }

    public function getEduAdmin() {
        return $this->eduAdmin;
    }

    public function setEduAdmin(EduAdmins $eduAdmin=null) {
        $this->eduAdmin = $eduAdmin;
    }

    public function getRegionEduAdmin() {
        return $this->regionEduAdmin;
    }

    public function setRegionEduAdmin(RegionEduAdmins $regionEduAdmin=null) {
        $this->regionEduAdmin = $regionEduAdmin;
    }

    public function getImplementationEntity() {
        return $this->implementationEntity;
    }

    public function setImplementationEntity(ImplementationEntities $implementationEntity=null) {
        $this->implementationEntity = $implementationEntity;
    }

    public function getUnitType() {
        return $this->unitType;
    }

    public function setUnitType(UnitTypes $unitType=null) {
        $this->unitType = $unitType;
    }

    public function getPrefecture() {
        return $this->prefecture;
    }

    public function setPrefecture(Prefectures $prefecture=null) {
        $this->prefecture = $prefecture;
    }

    public function getState() {
        return $this->state;
    }

    public function setState(States $state=null) {
        $this->state = $state;
    }

    public function getCategory() {
        return $this->category;
    }

    public function setCategory(UnitCategory $category=null) {
        $this->category = $category;
    }

    public function getMunicipality() {
        return $this->municipality;
    }

    public function setMunicipality(Municipalities $municipality = null) {
        $this->municipality = $municipality;
    }

    public function getManager() {
        return $this->manager;
    }

    public function setManager(Workers $manager=null) {
        $this->manager = $manager;
    }

    public function getResponsibles() {
        return $this->responsibles;
    }

    public function setResponsibles(Workers $responsibles=null) {
        $this->responsibles = $responsibles;
    }

    public function getDeletedAt() {
        return $this->deletedAt;
    }

    public function setDeletedAt($deletedAt=null) {
        $this->deletedAt = $deletedAt;
    }

    public function isActive() {
        return !isset($this->deletedAt);
    }

    public function __toString() {
        return 'SUS: '.$this->getUnitId().' | ΜΜ: '.$this->getMmSyncId().' | '.$this->getName();
    }
}
