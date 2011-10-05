<?php

	Class PageManager {

		public static function listAll(){
			if (Symphony::Database()->query("
				SELECT `id`, `parent`, `title`, `handle`
				FROM `tbl_pages`
				ORDER BY `title` ASC
			")) {
				$pages = Symphony::Database()->fetch();
			}

			$results = array();
			self::pageWalkRecursive(NULL, $pages, $results);

			return $results;
		}

		private static function pageWalkRecursive($parent_id, $pages, &$results) {
			if (!is_array($pages)) return;

			foreach($pages as $page) {
				if ($page->parent == $parent_id) {
					$results[] = array(
						'id' => $page->id,
						'title' => $page->title,
						'handle' => $page->handle,
						'children' => NULL
					);

					self::pageWalkRecursive($page->id, $pages, $results[count($results) - 1]['children']);
				}
			}
		}

		public static function flatView() {
			$pages = $this->listAll();

			$results = array();
			self::buildFlatView(NULL, $pages, $results);
			
			return $results;
		}
		
		private static function buildFlatView($path, $pages, &$results) {
			if (!is_array($pages)) return;

			foreach($pages as $page) {
				$label = ($path == NULL) ? $page['title'] : $path . ' / ' . $page['title'];

				$results[] = array(
					'id' => $page['id'],
					'title' => $label,
					'handle' => $page['handle'],
				);

				self::buildFlatView($label, $page['children'], $results);
				$label = $path;
			}
		}


	}

