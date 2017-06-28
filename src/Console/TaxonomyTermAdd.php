<?php

namespace Brisum\Wordpress\Optimization\Console;

use Brisum\Lib\Console\Command;
use WP_Error;
use wpdb;

class TaxonomyTermAdd extends Command
{
	protected $signature = 'taxonomy-term:add {postIds} {to}';

	protected $description = 'Move taxonomy term';

	public function handle(wpdb $wpdb)
	{
		$postIds = array_map('trim', explode(',', $this->argument('postIds')));
		list($toTaxonomy, $toTermSlug, $toIsAppend) = explode(':', $this->argument('to'));

        foreach ($postIds as $postId) {
	        if (!is_numeric($toTaxonomy)) {
		        die("Wrong <postIds> parameter, postId: {$postId}\n");
	        }
        }
		if (empty($toTaxonomy)) {
			die("Empty <toTaxonomy> parameter\n");
		}
		if (empty($toTermSlug)) {
			die("Empty <toTermSlug> parameter\n");
		}

		$toTerm = get_term_by('slug', $toTermSlug, $toTaxonomy);
		if (is_wp_error($toTerm)) {
			/** @var  WP_Error $toTerm */
			die($toTerm->get_error_message());
		}
		$toIsAppend = boolval($toIsAppend);

		foreach ($postIds as $postId) {
			$d = wp_set_post_terms(intval($postId), [$toTerm->term_id], $toTerm->taxonomy, $toIsAppend);
			echo "add post {$postId}\n";
		}
	}
}
