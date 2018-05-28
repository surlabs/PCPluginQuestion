<?php
/**
 * Copyright (c) 2018 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv3, see docs/LICENSE
 */

/**
 * Importer class for the PCPluginQuestion Plugin
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 *
 * @ingroup ServicesCOPage
 */
class ilPCPluginQuestionImporter extends ilPageComponentPluginImporter
{
	public function init()
	{
	}


	/**
	 * Import xml representation
	 *
	 * @param	string			$a_entity
	 * @param	string			$a_id
	 * @param	string			$a_xml
	 * @param	ilImportMapping	$a_mapping
	 */
	public function importXmlRepresentation($a_entity, $a_id, $a_xml, $a_mapping)
	{
		/** @var ilTestPageComponentPlugin $plugin */
		$plugin = ilPluginAdmin::getPluginObject(IL_COMP_SERVICE, 'COPage', 'pgcp', 'TestPageComponent');

		$new_id = self::getPCMapping($a_id, $a_mapping);

		$properties = self::getPCProperties($new_id);
		$version = self::getPCVersion($new_id);

		// write the mapped file id to the properties
		if ($old_file_id = $properties['page_file'])
		{
			$new_file_id = $a_mapping->getMapping("Modules/File", 'file', $old_file_id);
			$properties['page_file'] = $new_file_id;
		}

		// save the data from the imported xml and write its id to the properties
		if ($additional_data_id = $properties['additional_data_id'])
		{
			$data = html_entity_decode(substr($a_xml, 6, -7));
			$id = $plugin->saveData($data);
			$properties['additional_data_id'] = $id;
		}

		self::setPCProperties($new_id, $properties);
		self::setPCVersion($new_id, $version);
	}
}