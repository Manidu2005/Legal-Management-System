<?php

namespace Database\Seeders;

use App\Models\CaseCategory;
use Illuminate\Database\Seeder;

/**
 * Seeds the full 3-level Sri Lankan case-type taxonomy:
 *
 *   Level 1 — Main Type    (Civil / Criminal / Constitutional & Public Law)
 *   Level 2 — Group        (e.g. "Property & Land Law")
 *   Level 3 — Specific type / leaf (what a case actually gets filed under)
 *
 * Mirrors Parts 2, 3 and 4 of the "Sri Lankan Law: A Complete Categorization
 * of Case Types" reference. Every group gets a trailing "Other" leaf so
 * every case is always categorizable even if it doesn't fit a named type.
 */
class CaseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $taxonomy = $this->taxonomy();

        $mainSort = 0;
        foreach ($taxonomy as $mainName => $groups) {
            $mainSort++;
            $main = CaseCategory::create([
                'name' => $mainName,
                'parent_id' => null,
                'level' => CaseCategory::LEVEL_MAIN_TYPE,
                'sort_order' => $mainSort,
                'is_active' => true,
            ]);

            $groupSort = 0;
            foreach ($groups as $groupName => $leaves) {
                $groupSort++;
                $group = CaseCategory::create([
                    'name' => $groupName,
                    'parent_id' => $main->id,
                    'level' => CaseCategory::LEVEL_GROUP,
                    'sort_order' => $groupSort,
                    'is_active' => true,
                ]);

                if (! in_array('Other', $leaves, true)) {
                    $leaves[] = 'Other';
                }

                $leafSort = 0;
                foreach ($leaves as $leafName) {
                    $leafSort++;
                    CaseCategory::create([
                        'name' => $leafName,
                        'parent_id' => $group->id,
                        'level' => CaseCategory::LEVEL_SPECIFIC_TYPE,
                        'sort_order' => $leafSort,
                        'is_active' => true,
                    ]);
                }
            }
        }

        $this->command?->info('✓ Seeded case category taxonomy: ' . CaseCategory::count() . ' categories');
    }

    /**
     * @return array<string, array<string, array<int, string>>>
     */
    private function taxonomy(): array
    {
        return [
            'Civil' => [
                'Contract & General Commercial' => [
                    'Simple debt / money-recovery actions',
                    'Breach of contract',
                    'Sale of goods disputes',
                    'Loan recovery actions',
                    'Mortgage / hypothecary actions',
                    'Parate execution disputes',
                    'Debt Recovery (Special Provisions) Act / Recovery of Loans by Banks Act actions',
                    'Insurance claims and disputes',
                    'Suretyship / guarantee disputes',
                    'Unjust enrichment / negotiorum gestio',
                    'Hire-purchase and leasing disputes',
                ],
                'Company, Insolvency & Commercial Regulatory Law' => [
                    'Company law disputes (oppression, mismanagement, derivative actions, share disputes)',
                    'Directors\' duties claims',
                    'Winding up — compulsory (court petition)',
                    'Winding up — members\' voluntary',
                    'Winding up — creditors\' voluntary',
                    'Winding up — court-supervised',
                    'Individual and partnership insolvency (sequestration, discharge of bankrupts)',
                    'Corporate restructuring / rescue',
                    'Arbitration-related court applications',
                    'Securities / capital markets enforcement',
                ],
                'Intellectual Property' => [
                    'Patent infringement and validity',
                    'Trademark / service-mark infringement, opposition and nullity',
                    'Copyright infringement',
                    'Industrial design disputes',
                    'Geographical indication disputes',
                    'Appeal from a Director-General of Intellectual Property decision',
                ],
                'Admiralty & Maritime' => [
                    'Ownership / possession dispute over a ship',
                    'Dispute between co-owners of a vessel',
                    'Mortgage or charge over a ship',
                    'Cargo damage, collision, salvage or towage claim',
                    'Seafarers\' wages claim',
                ],
                'Property & Land Law' => [
                    'Rei vindicatio (recovery of ownership)',
                    'Declaration of title',
                    'Partition action',
                    'Possessory interdict',
                    'Servitudes and easements',
                    'Boundary and encroachment dispute',
                    'Prescription claim',
                    'Landlord–tenant dispute (Rent Act)',
                    'State land dispute',
                    'Land acquisition compensation dispute',
                    'Co-ownership / joint-property dispute',
                    'Section 66 breach-of-the-peace land dispute',
                ],
                'Family Law' => [
                    'Divorce — fault-based (general law)',
                    'Divorce — Kandyan',
                    'Divorce — Muslim (talak / fasah / khula)',
                    'Judicial separation',
                    'Nullity of marriage',
                    'Maintenance — spousal',
                    'Maintenance — child',
                    'Child custody and guardianship',
                    'Adoption',
                    'Domestic violence protection order',
                    'Paternity and legitimacy dispute',
                    'Dowry (kaikuli) / mahar recovery',
                    'International child abduction',
                ],
                'Succession & Testamentary' => [
                    'Probate of a will (contested or uncontested)',
                    'Letters of administration (intestate succession)',
                    'Caveat against probate / administration',
                    'Muslim intestate succession',
                    'Kandyan intestate succession',
                    'Thesawalamai succession and matrimonial property',
                    'Trusts and fiduciary disputes',
                ],
                'Delict (Tort)' => [
                    'Negligence — motor vehicle accident',
                    'Negligence — medical / professional',
                    'Negligence — occupier\'s liability',
                    'Defamation (libel or slander)',
                    'Nuisance',
                    'Assault, trespass to the person, false imprisonment, malicious prosecution',
                    'Trespass to property and conversion',
                    'Product liability',
                    'Vicarious liability claim',
                ],
                'Labour & Employment' => [
                    'Unjustified / unfair termination',
                    'Gratuity claim',
                    'EPF / ETF contribution dispute',
                    'Collective industrial dispute',
                    'Trade union recognition / inter-union dispute',
                    'Workmen\'s compensation (workplace injury)',
                    'Public / state-sector employment dispute',
                ],
                'Tax & Revenue' => [
                    'Income tax assessment appeal',
                    'VAT, excise duty or customs valuation dispute',
                    'Stamp duty dispute',
                ],
                'Consumer Protection' => [
                    'Complaint to the Consumer Affairs Authority',
                    'Competition-law complaint (anti-competitive conduct)',
                ],
                'Environmental Civil Matters' => [
                    'Challenge to an EIA approval / Central Environmental Authority decision',
                    'Pollution and licensing dispute',
                ],
            ],
            'Criminal' => [
                'Offences Against the State & Public Order' => [
                    'Waging or abetting war against the state; sedition',
                    'Offences relating to the President',
                    'Mutiny, desertion or abetting military offences',
                    'Unlawful assembly, rioting, affray',
                    'Corruption-adjacent misconduct in public office (Penal Code, not Anti-Corruption Act)',
                    'Election offences under the Penal Code (bribery of voters, personation, obstruction)',
                    'Contempt of lawful authority',
                    'False evidence and offences against public justice (perjury, fabricating evidence, harbouring an offender)',
                    'Offences relating to coin and government stamps',
                    'Offences relating to weights and measures',
                    'Offences affecting public health, safety, convenience, decency and morals',
                    'Offences relating to religion',
                    'Criminal intimidation, insult and annoyance',
                    'Unlawful oaths',
                ],
                'Offences Against the Human Body' => [
                    'Murder',
                    'Culpable homicide not amounting to murder',
                    'Causing death by a rash or negligent act',
                    'Hurt and grievous hurt',
                    'Wrongful restraint and wrongful confinement',
                    'Kidnapping and abduction',
                    'Rape / statutory rape',
                    'Incest',
                    'Grave sexual abuse',
                    'Sexual harassment',
                    'Unnatural offences and acts of gross indecency',
                    'Cruelty to and offences against children',
                ],
                'Offences Against Property' => [
                    'Theft',
                    'Extortion',
                    'Robbery (including aggravated robbery with weapons)',
                    'Criminal misappropriation of property',
                    'Criminal breach of trust',
                    'Receiving stolen property',
                    'Cheating (including cheating by personation)',
                    'Fraudulent deeds or dispositions',
                    'Mischief (malicious damage to property)',
                    'Illegal removal of wrecks',
                    'Criminal trespass and house-breaking / burglary',
                ],
                'Documents, Currency & Forgery' => [
                    'Forgery',
                    'Using a forged document',
                    'Counterfeiting bank notes / currency',
                    'Counterfeiting coin or government stamps',
                ],
                'Narcotics Offences' => [
                    'Possession of narcotics',
                    'Trafficking',
                    'Manufacture (heroin, cocaine, morphine, opium)',
                    'High-seas drug manufacture (2026 amendment)',
                ],
                'Bribery & Corruption' => [
                    'Soliciting or accepting gratification',
                    'Unexplained wealth',
                    'False asset declarations',
                ],
                'Money Laundering & Terrorist Financing' => [
                    'Money laundering offence',
                    'Terrorist financing offence',
                ],
                'Terrorism-Related Offences' => [
                    'Offence under the Prevention of Terrorism (Temporary Provisions) Act',
                ],
                'Cybercrime' => [
                    'Unauthorized access',
                    'Unauthorized modification of data',
                    'Illegal interception',
                    'Unlawful devices',
                    'Prohibited "false" online statement / inauthentic account (Online Safety Act)',
                ],
                'Election-Law Offences' => [
                    'Offence under a specific election statute',
                ],
                'Excise & Customs Offences' => [
                    'Illicit liquor manufacture or sale (excise)',
                    'Smuggling',
                    'Under-valuation (customs)',
                ],
                'Firearms Offences' => [
                    'Unlawful possession of firearms',
                ],
                'Immigration & Human-Trafficking Offences' => [
                    'Immigration / emigration offence',
                    'People-smuggling / human trafficking',
                ],
                'Wildlife & Forest Offences' => [
                    'Fauna and Flora Protection Ordinance offence',
                    'Forest Ordinance offence',
                ],
                'Traffic Offences' => [
                    'Motor Traffic Act offence',
                ],
                'Food & Drug Safety Offences' => [
                    'Food Act offence',
                    'Cosmetics Devices and Drugs Act offence',
                ],
                'Juvenile Justice' => [
                    'Juvenile Court proceedings',
                    'Care and protection order',
                    'Diversion to remand home, approved school or probation',
                ],
                'Military / Service Offences' => [
                    'Court martial (Army / Navy / Air Force Act)',
                ],
            ],
            'Constitutional & Public Law' => [
                'Fundamental Rights Applications' => [
                    'Freedom from torture and cruel, inhuman or degrading treatment',
                    'Freedom from arbitrary arrest and detention',
                    'Right to equality and equal protection of the law',
                    'Freedom of speech, assembly and association',
                    'Freedom of movement and choice of residence',
                    'Freedom of religion',
                    'Language rights',
                    'Right to a fair trial',
                ],
                'Pre-Enactment (Special Determination) of Bills' => [
                    'Special determination of a Bill\'s constitutionality',
                ],
                'Interpretation of the Constitution' => [
                    'Constitutional interpretation reference',
                ],
                'Election Petitions & Referendum Validity' => [
                    'Election petition',
                    'Referendum validity dispute',
                    'Parliamentary-privilege breach',
                ],
                'Administrative Law / Writ Jurisdiction' => [
                    'Certiorari',
                    'Mandamus',
                    'Prohibition',
                    'Quo warranto',
                    'Habeas corpus',
                ],
            ],
        ];
    }
}
