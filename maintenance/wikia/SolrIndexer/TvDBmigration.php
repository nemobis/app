<?php
/**
 * Created by adam
 * Date: 05.03.14
 */

require_once( dirname( __FILE__ ) . '/../../Maintenance.php' );

class TvDBmigration extends Maintenance {

	protected $sw = [];
	protected $s = [];
	protected $codes = [];
	protected $added = [];

	/**
	 * Do the actual work. All child classes will need to implement this
	 */
	public function execute() {
		$host = $this->getOption('host');
		$user = $this->getOption('user');
		$dbname = $this->getOption('db');
		if ( !$host || !$user || !$dbname ) {
			die(1);
		}
		$d = $this->getData( $host, $user, $dbname );
		echo count($d)."\n";
		foreach( $d as $row ) {
			$lookup = $this->setSeriesInfo($row);
			$this->setWikiInfo($row['wiki_id'], $lookup);
		}

		$this->saveToFile();
		$this->save();
	}

	protected function saveToFile() {
		$f = fopen( 'series_wikis.csv', 'w' );
		foreach( $this->sw as $line ) {
			fputcsv( $f, $line );
		}
		fclose( $f );
		$f = fopen( 'series.csv', 'w' );
		foreach( $this->s as $line ) {
			fputcsv( $f, $line );
		}
		fclose( $f );
	}

	protected function save() {
		$db = $this->getConnection();
		if ( !empty( $this->s ) ) {
			foreach( $this->s as $series ) {
				try {
					$db->insert('tv_series', $series );
				} catch (Exception $e) {
					echo $e->getMessage()."\n";
				}
			}
		}
		if ( !empty( $this->sw ) ) {
			foreach( $this->sw as $sw ) {
				try {
					$db->insert('tv_series_wikis', $sw );
				} catch (Exception $e) {
					echo $e->getMessage()."\n";
				}
			}
		}
	}

	protected function setSeriesInfo($data) {
		if ( empty($this->added[ $data[ 'tvrage_id' ] ] ) ) {
			$rage = $this->getRageData($data['tvrage_id']);
			$lookup = $this->getLookup($data['title']);

			$this->added[ $data[ 'tvrage_id' ] ] = $lookup;
			//main title
			$this->s[] = [
				'series_name' => $data['title'],
				'series_lang' => $this->getLangFromCountry($data['country']),
				'series_lookup' => $lookup
			];

			foreach( $rage->akas->aka as $alternative ) {
				$countryCode = null;
				foreach( $alternative->attributes() as $attr => $value ) {
					if ( strpos( strtolower( strval( $value ) ), 'season') !== false ) {
						continue 2;
					}
					if ( $attr == 'country' ) {
						$countryCode = strval($value);
					}
				}
				if ( $countryCode ) {
					$this->s[] = [
						'series_name' => strval($alternative),
						'series_lang' => $this->getLangFromCountry($countryCode),
						'series_lookup' => $lookup
					];
				}
			}
			echo $lookup."\n";
		}
		return $this->added[$data['tvrage_id']];
	}

	protected function getLookup($title) {
		$title = html_entity_decode($title);
		$title = strtolower( $title );
		return preg_replace( '|\W+|s', '', $title );
	}

	protected function getRageData($id) {
		$conn = curl_init('http://services.tvrage.com/feeds/showinfo.php?sid='.$id);
		curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1 );
		$res = curl_exec($conn);
		curl_close($conn);
		$xml = simplexml_load_string($res);
		return $xml;
	}

	protected function setWikiInfo($wikiId, $lookup) {
		$info = WikiFactory::getWikiByID($wikiId);
		if ( !empty( $info ) ) {
			$this->sw[] = [
				'series_lookup' => $lookup,
				'wiki_id' => intval( $wikiId ),
				'wiki_name' => $info->city_title,
				'wiki_lang' => strtolower( explode( '-', $info->city_lang )[0] )
			];
		} else {
			echo "Empty wiki factory result: ". $wikiId."\n";
		}
		return true;
	}

	protected function getData( $h, $u, $d ) {
		//manually curated wikis
		$wikiMap = [
			769071 => 358337,
			774679 => 13346
		];
		$connection = mysql_connect($h, $u);
		mysql_select_db($d, $connection);
		$q = 'SELECT * FROM fact_tv_official_wikis off, lookup_tv_shows sh WHERE off.tvrage_id = sh.tvrage_id';
		$r = mysql_query($q);
		$result = [];
		while( $row = mysql_fetch_array($r) ) {
			if ( isset( $wikiMap[ $row['wiki_id'] ] ) ) {
				$row['wiki_id'] = $wikiMap[ $row['wiki_id'] ];
			}
			$result[] = $row;
		}
		mysql_close($connection);
		return $result;
	}

	protected function getLangFromCountry($country) {
		if ( empty( $this->codes ) ) {
			$data = file_get_contents('c_to_l.txt');
			$lines = explode("\n", $data);
			foreach( $lines as $line ) {
				$lang = null;
				$fields = explode("\t", $line);
				$fullLang = explode(',', $fields[15]);
				$extLang = explode( '-', $fullLang[0] );
				$lang = strtolower( $extLang[0] );
				//for english check if US or UK
				if ( $lang == 'en' &&
					!empty( $fullLang[1] ) &&
					( isset( $extLang[1] ) && !in_array( strtolower( $extLang[1] ), [ 'gb', 'us' ] ) )
				) {
					//else get second lang
					$lang = strtolower( explode( '-', $fullLang[1] )[0] );
				}
				if ( !empty( $lang ) ) {
					$this->codes[strtolower($fields[0])] = strtolower($lang);
					$this->codes[strtolower($fields[1])] = strtolower($lang);
					$this->codes[strtolower($fields[3])] = strtolower($lang);
				}
			}
		}
		$norm = strtolower($country);
		return (isset($this->codes[$norm])) ? $this->codes[$norm] : false;
	}

	protected function getConnection() {
		global $wgExternalDatawareDB;
		if ( !isset( $this->db ) ) {
			$this->db = wfGetDB(DB_MASTER, array(), $wgExternalDatawareDB);
		}
		return $this->db;
	}
}

$maintClass = 'TvDBmigration';
require( RUN_MAINTENANCE_IF_MAIN );