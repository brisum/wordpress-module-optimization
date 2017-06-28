<?php

namespace Brisum\Wordpress\Optimization\Console;

use Brisum\Lib\Console\Command;
use WP_Error;
use WP_Term;
use wpdb;

class TaxonomyTermRemove extends Command
{
	protected $signature = 'optimization:taxonomy-term:remove {selector} {remove}';

	protected $description = 'Remove taxonomy term';

	public function handle(wpdb $wpdb)
	{
		$from = $this->parseParam($this->argument('selector'));
		$termsToRemove = $this->parseParam($this->argument('remove'));
		$query = "
			SELECT p.ID
			FROM {$wpdb->posts} p
			[JOIN]
			WHERE p.post_type = 'product' AND [WHERE]";
		$join = [];
		$where = [];

		foreach ($from as $taxonomy => $terms) {
			$termTaxonomyIds = [];
			foreach ($terms as $term) {
				$termTaxonomyIds[] = $term->term_taxonomy_id;
			}

			$join[] = "INNER JOIN {$wpdb->term_relationships} tr_{$taxonomy} on
					tr_{$taxonomy}.object_id = p.ID";
			$where[] = "tr_{$taxonomy}.term_taxonomy_id in (" . implode(',', $termTaxonomyIds) . ")";
		}

		$query = str_replace('[JOIN]', implode(' ', $join), $query);
		$query = str_replace('[WHERE]', implode(' AND ', $where), $query);
		$postIds= $wpdb->get_col($query);

		foreach ($postIds as $postId) {
			foreach ($termsToRemove as $taxonomy => $terms) {
				$termIds = [];
				foreach ($terms as $term) {
					$termIds[] = $term->term_id;
				}

				$d = wp_remove_object_terms(intval($postId), $termIds, $taxonomy);
			}

			echo "remove terms from post {$postId}\n";
		}
	}

	protected function parseParam($param)
	{
		$terms = [];

		foreach (explode('&', $param) as $taxonomyTerm) {
			list($taxonomy, $termSlug) = explode('=', $taxonomyTerm);

			if (empty($taxonomy)) {
				die("Empty <taxonomy> parameter\n");
			}
			if (empty($termSlug)) {
				die("Empty <termSlug> parameter\n");
			}

			$term = get_term_by('slug', $termSlug, $taxonomy);
			if (is_wp_error($term)) {
				/** @var  WP_Error $term */
				die($term->get_error_message());
			}

			/** @var WP_Term $term */
			$terms[$term->taxonomy][$term->term_id] = $term;
		}

		return $terms;
	}
}
