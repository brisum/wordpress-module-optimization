<?php

namespace Brisum\Wordpress\Optimization\Console;

use Brisum\Lib\Console\Command;
use WP_Error;
use wpdb;

class TaxonomyTermMove extends Command
{
	protected $signature = 'taxonomy-term:move {from} {to}';

	protected $description = 'Move taxonomy term';

	public function handle(wpdb $wpdb)
	{
		list($fromTaxonomy, $fromTermSlug) = explode(':', $this->argument('from'));
		list($toTaxonomy, $toTermSlug) = explode(':', $this->argument('to'));

		if (empty($fromTaxonomy)) {
			die("Empty <fromTaxonomy> parameter\n");
		}
		if (empty($fromTermSlug)) {
			die("Empty <fromTermSlug> parameter\n");
		}
		if (empty($toTaxonomy)) {
			die("Empty <toTaxonomy> parameter\n");
		}
		if (empty($toTermSlug)) {
			die("Empty <toTermSlug> parameter\n");
		}


		$fromTerm = get_term_by('slug', $fromTermSlug, $fromTaxonomy);
		$toTerm = get_term_by('slug', $toTermSlug, $toTaxonomy);
		if (is_wp_error($fromTerm)) {
			/** @var  WP_Error $fromTerm */
			die($fromTerm->get_error_message());
		}
		if (is_wp_error($toTerm)) {
			/** @var  WP_Error $toTerm */
			die($toTerm->get_error_message());
		}

		$postIds= $wpdb->get_col($wpdb->prepare(
			"SELECT
				p.ID
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->term_relationships} tr on
				tr.object_id = p.ID
			WHERE
				tr.term_taxonomy_id = '%d'
				and p.post_type = 'product'",
			$fromTerm->term_taxonomy_id
		));

		foreach ($postIds as $postId) {
			$d = wp_remove_object_terms(intval($postId), [$fromTerm->term_id], $fromTerm->taxonomy);
			$d = wp_set_post_terms(intval($postId), [$toTerm->term_id], $toTerm->taxonomy, true);
			echo "move post {$postId}\n";
		}
	}
}
