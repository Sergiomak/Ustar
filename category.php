<div class="row category-page-row">

		<div class="col large-3 hide-for-medium <?php flatsome_sidebar_classes(); ?>">
			<div id="shop-sidebar" class="sidebar-inner col-inner">
				<?php
				  if(is_active_sidebar('shop-sidebar')) {
				  		dynamic_sidebar('shop-sidebar');
				  	} else{ echo '<p>You need to assign Widgets to <strong>"Shop Sidebar"</strong> in <a href="'.get_site_url().'/wp-admin/widgets.php">Appearance > Widgets</a> to show anything here</p>';
				  }
				?>
			</div><!-- .sidebar-inner -->
		</div><!-- #shop-sidebar -->

		<div class="col large-9">
		<?php
		/**
		 * Hook: woocommerce_before_main_content.
		 *
		 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
		 * @hooked woocommerce_breadcrumb - 20 (FL removed)
		 * @hooked WC_Structured_Data::generate_website_data() - 30
		 */
		do_action( 'woocommerce_before_main_content' );

		?>

		<?php
		/**
		 * Hook: woocommerce_archive_description.
		 *
		 * @hooked woocommerce_taxonomy_archive_description - 10
		 * @hooked woocommerce_product_archive_description - 10
		 */
		do_action( 'woocommerce_archive_description' );
		?>

		<?php

		if ( fl_woocommerce_version_check( '3.4.0' ) ? woocommerce_product_loop() : have_posts() ) {

			/**
			 * Hook: woocommerce_before_shop_loop.
			 *
			 * @hooked wc_print_notices - 10
			 * @hooked woocommerce_result_count - 20 (FL removed)
			 * @hooked woocommerce_catalog_ordering - 30 (FL removed)
			 */
			do_action( 'woocommerce_before_shop_loop' );

			woocommerce_product_loop_start();

			if ( wc_get_loop_prop( 'total' ) ) {
				while ( have_posts() ) {
					the_post();

					/**
					 * Hook: woocommerce_shop_loop.
					 *
					 * @hooked WC_Structured_Data::generate_product_data() - 10
					 */
					do_action( 'woocommerce_shop_loop' );

					wc_get_template_part( 'content', 'product' );
				}
			}

			woocommerce_product_loop_end();

			global $wp_query;
			$globalTaxQuery = $wp_query->query_vars['tax_query'];
			$isFilterUse = count($globalTaxQuery) > 2;
			$productIds = array_map(function ($product)
			{
				return $product->ID;
			}, $wp_query->posts);
			
			if (is_tax('product_cat')) {
				$queriedObject = get_queried_object();

				if (wc_get_loop_prop( 'total' ) < 10 && $queriedObject->parent != 0) {
					$args = [
						'post_type' => 'product',
						'posts_per_page' => 9,
						'tax_query' => [
							'relation' => 'AND',
							[
								'taxonomy' => 'product_cat',
								'terms' => $queriedObject->parent,
							],
							[
								'taxonomy' => 'product_cat',
								'terms' => $queriedObject->term_id,
								'operator' => 'NOT IN'
							]
						],
						'meta_query' => [
							[
								'key' => '_stock_status',
								'value' => 'instock'
							]
						]
					];

					if ($isFilterUse) {
						unset($globalTaxQuery['relation']);

						$args['tax_query'] = [
							'relation' => 'AND',
							[
								'taxonomy' => 'product_cat',
								'terms' => $queriedObject->term_id
							]
						];
						$args['post__not_in'] = $productIds;

						/*foreach ($globalTaxQuery as $key => $value) {
							if ($value['taxonomy'] == 'product_visibility') {
								continue;
							}

							$value['operator'] = 'NOT IN';
							$args['tax_query'][] = $value;
						}*/
					}

					$loop = new WP_Query( $args );

					if ($isFilterUse && $loop->post_count < 9) {
						$args = [
							'post_type' => 'product',
							'posts_per_page' => 9,
							'tax_query' => [
								'relation' => 'AND',
								[
									'taxonomy' => 'product_cat',
									'terms' => $queriedObject->parent,
								],
								[
									'taxonomy' => 'product_cat',
									'terms' => $queriedObject->term_id,
									'operator' => 'NOT IN'
								]
							],
							'meta_query' => [
								[
									'key' => '_stock_status',
									'value' => 'instock'
								]
							]
						];

						$loop = new WP_Query( $args );
					}

					if ($loop->post_count < 9) {
						$currentCat = $queriedObject;
						$prevCat = $queriedObject;

						while ($loop->post_count < 9) {
							if ($currentCat->parent == 0) {
								$args = [
									'post_type' => 'product',
									'posts_per_page' => 9,
									'tax_query' => [
										'relation' => 'AND',
										[
											'taxonomy' => 'product_cat',
											'field' => 'name',
											'terms' => 'Boty'
										]
									],
									'meta_query' => [
										[
											'key' => '_stock_status',
											'value' => 'instock'
										]
									]
								];

								$loop = new WP_Query( $args );

								break;
							}

							$currentCat = get_term($currentCat->parent, 'product_cat');

							$args = [
								'post_type' => 'product',
								'posts_per_page' => 9,
								'tax_query' => [
									'relation' => 'AND',
									[
										'taxonomy' => 'product_cat',
										'terms' => $currentCat->term_id,
									],
									[
										'taxonomy' => 'product_cat',
										'terms' => $prevCat->term_id,
										'operator' => 'NOT IN'
									]
								],
								'meta_query' => [
									[
										'key' => '_stock_status',
										'value' => 'instock'
									]
								]
							];

							$loop = new WP_Query( $args );

							$prevCat = $currentCat;
						}
					}

					if ( $loop->have_posts() ) {

						echo '<h2>Zbývající produkty našeho katalogu</h2>';

						woocommerce_product_loop_start();
						
						while ($loop->have_posts() ) {
							$loop->the_post();
							wc_get_template_part( 'content', 'product' );
						}

						woocommerce_product_loop_end();
					} else {
						//echo __( 'No products found' );
					}

					wp_reset_postdata();
				} else if (wc_get_loop_prop( 'total' ) < 10) {
					$args = [
						'post_type' => 'product',
						'posts_per_page' => 9,
						'tax_query' => [
							'relation' => 'AND',
							[
								'taxonomy' => 'product_cat',
								'field' => 'name',
								'terms' => 'Boty'
							]
						],
						'meta_query' => [
							[
								'key' => '_stock_status',
								'value' => 'instock'
							]
						]
					];

					if ($isFilterUse) {
						unset($globalTaxQuery['relation']);

						$args['tax_query'] = [
							'relation' => 'AND',
							[
								'taxonomy' => 'product_cat',
								'terms' => $queriedObject->term_id
							]
						];
						$args['post__not_in'] = $productIds;

						/*foreach ($globalTaxQuery as $key => $value) {
							if ($value['taxonomy'] == 'product_visibility') {
								continue;
							}

							$value['operator'] = 'NOT IN';
							$args['tax_query'][] = $value;
						}*/
					}

					$loop = new WP_Query( $args );

					if ($isFilterUse && $loop->post_count < 9) {
						$args = [
							'post_type' => 'product',
							'posts_per_page' => 9,
							'tax_query' => [
								'relation' => 'AND',
								[
									'taxonomy' => 'product_cat',
									'field' => 'name',
									'terms' => 'Boty'
								]
							],
							'meta_query' => [
								[
									'key' => '_stock_status',
									'value' => 'instock'
								]
							]
						];

						$loop = new WP_Query( $args );
					}

					if ( $loop->have_posts() ) {

						echo '<h2>Zbývající produkty našeho katalogu</h2>';

						woocommerce_product_loop_start();
						
						while ($loop->have_posts() ) {
							$loop->the_post();
							wc_get_template_part( 'content', 'product' );
						}

						woocommerce_product_loop_end();
					} else {
						//echo __( 'No products found' );
					}

					wp_reset_postdata();
				}
			}
			
			/**
			 * Hook: woocommerce_after_shop_loop.
			 *
			 * @hooked woocommerce_pagination - 10
			 */
			do_action( 'woocommerce_after_shop_loop' );
		} else {
			/**
			 * Hook: woocommerce_no_products_found.
			 *
			 * @hooked wc_no_products_found - 10
			 */
			do_action( 'woocommerce_no_products_found' );
		}
		?>

		<?php
			/**
			 * Hook: flatsome_products_after.
			 *
			 * @hooked flatsome_products_footer_content - 10
			 */
			do_action( 'flatsome_products_after' );
			/**
			 * Hook: woocommerce_after_main_content.
			 *
			 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
			 */
			do_action( 'woocommerce_after_main_content' );
		?>

		</div>
</div>
