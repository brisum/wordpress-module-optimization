<?php

namespace Brisum\Wordpress\Optimization\Console;

use Brisum\Lib\Console\Command;
use WP_Term;
use wpdb;

class TaxonomyTermNormalizeHierarchy extends Command
{
	protected $signature = 'optimization:taxonomy-term:normalize-hierarchy {taxonomy}';

	protected $description = 'Normalize taxonomy term hierarchy';

	public function handle(wpdb $wpdb)
	{
		$taxonomy = $this->argument('taxonomy');
		$termsArgs = ['taxonomy' => $taxonomy];
		$termsByParent = [];

		foreach(get_terms($termsArgs) as $term) {
			/** @var WP_Term $term */
			$termsByParent[$term->parent][$term->term_id] = $term;
		}

		foreach ($termsByParent[0] as $termLevel1) {
			/** @var WP_Term $termsLevel1 */
			/** @var WP_Term[] $termsLevel2 */
			$termsLevel2 = isset($termsByParent[$termLevel1->term_id]) ? $termsByParent[$termLevel1->term_id] : [] ;

			foreach ($termsLevel2 as $termLevel2) {
				/** @var WP_Term $termsLevel2 */
				/** @var WP_Term[] $termsLevel3 */
				$termsLevel3 = isset($termsByParent[$termLevel2->term_id]) ? $termsByParent[$termLevel2->term_id] : [];

				foreach ($termsLevel3 as $termLevel3) {
					/** @var WP_Term $termsLevel3 */
					/** @var WP_Term[] $termsLevel4 */
					$termsLevel4 = isset($termsByParent[$termLevel3->term_id]) ? $termsByParent[$termLevel3->term_id] : [];
					$this->normalizeRelation($termsLevel3->term_id, array_keys($termsLevel4));
				}

				$this->normalizeRelation($termLevel2->term_id, array_keys($termsLevel3));
			}

			$termsLevel2 && $this->normalizeRelation($termLevel1->term_id, array_keys($termsLevel2));
		}
	}

	protected function normalizeRelation($parentId, $childIds){
		if (empty($childIds)) {
			return;
		}

		global $wpdb;
		$taxonomy = $this->argument('taxonomy');
		$postInChild = $wpdb->get_col(
			"SELECT
				p.ID
			FROM {$wpdb->posts} p
			inner join ns65_term_relationships tr on
				tr.object_id = p.ID
			inner join ns65_term_taxonomy tt on
				tt.term_taxonomy_id = tr.term_taxonomy_id
			inner join ns65_terms t on
				t.term_id = tt.term_id
				and t.term_id in (" . implode(', ', array_map('intval', $childIds)) . ")
			where p.post_status = 'publish'"
		);
		$postInParent = $wpdb->get_col(
			"SELECT
				p.ID
			FROM {$wpdb->posts} p
			inner join ns65_term_relationships tr on
				tr.object_id = p.ID
			inner join ns65_term_taxonomy tt on
				tt.term_taxonomy_id = tr.term_taxonomy_id
			inner join ns65_terms t on
				t.term_id = tt.term_id
				and t.term_id in (" . implode(', ', array_map('intval', [$parentId])) . ")
			where p.post_status = 'publish'"
		);

		$needToAdd = array_diff($postInChild, $postInParent);
		foreach ($needToAdd as $postId) {
			$d = wp_set_post_terms(intval($postId), [$parentId], $taxonomy, true);
		}
	}
}
