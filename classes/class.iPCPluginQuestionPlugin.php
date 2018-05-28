<?php
/**
 * Copyright (c) 2018 Institut fuer Lern-Innovation, Friedrich-Alexander-Universitaet Erlangen-Nuernberg
 * GPLv3, see docs/LICENSE
 */
 
/**
 * Plugin question Page Component plugin
 *
 * @author Fred Neumann <fred.neumann@ili.fau.de>
 */
class ilPCPluginQuestionPlugin extends ilPageComponentPlugin
{
	/**
	 * Get plugin name 
	 *
	 * @return string
	 */
	function getPluginName()
	{
		return "PCPluginQuestion";
	}


	/**
	 * Check if parent type is valid
	 *
	 * @return string
	 */
	function isValidParentType($a_parent_type)
	{
		// test with all parent types
		return true;
	}


	/**
	 * Handle an event
	 * @param string	$a_component
	 * @param string	$a_event
	 * @param mixed		$a_parameter
	 */
	public function handleEvent($a_component, $a_event, $a_parameter)
	{
		$_SESSION['pcplq_listened_event'] = array('time' => time(), 'event' => $a_event);
	}

	/**
	 * This function is called when the page content is cloned
	 * @param array 	$a_properties		properties saved in the page, (should be modified if neccessary)
	 * @param string	$a_plugin_version	plugin version of the properties
	 */
	public function onClone(&$a_properties, $a_plugin_version)
	{
		if ($file_id = $a_properties['page_file'])
		{
			try
			{
				include_once("./Modules/File/classes/class.ilObjFile.php");
				$fileObj = new ilObjFile($file_id, false);
				$newObj = clone($fileObj);
				$newObj->setId(null);
				$newObj->create();
				$newObj->createDirectory();
				$this->rCopy($fileObj->getDirectory(), $newObj->getDirectory());
				$a_properties['page_file'] = $newObj->getId();

				ilUtil::sendInfo("File Object $file_id cloned.", true);
			}
			catch (Exception $e)
			{
				ilUtil::sendFailure($e->getMessage(), true);
			}
		}

		if ($additional_data_id = $a_properties['additional_data_id'])
		{
			$data = $this->getData($additional_data_id);
			$id = $this->saveData($data);
			$a_properties['additional_data_id'] = $id;
		}
	}


	/**
	 * This function is called before the page content is deleted
	 * @param array 	$a_properties		properties saved in the page (will be deleted afterwards)
	 * @param string	$a_plugin_version	plugin version of the properties
	 */
	public function onDelete($a_properties, $a_plugin_version)
	{
		if ($file_id = $a_properties['page_file'])
		{
			try
			{
				include_once("./Modules/File/classes/class.ilObjFile.php");
				$fileObj = new ilObjFile($file_id, false);
				$fileObj->delete();

				ilUtil::sendInfo("File Object $file_id deleted.", true);
			}
			catch (Exception $e)
			{
				ilUtil::sendFailure($e->getMessage(), true);
			}
		}

		if ($additional_data_id = $a_properties['additional_data_id'])
		{
			$this->deleteData($additional_data_id);
		}
	}



	/**
	 * Get additional data by id
	 * @param $id
	 * @return string
	 */
	public function getData($id)
	{
		global $DIC;
		$db = $DIC->database();

		$query = "SELECT data FROM pctcp_data WHERE id = " .$db->quote($id, 'integer');
		$result = $db->query($query);
		if ($row = $db->fetchAssoc($result))
		{
			return $row['data'];
		}
		return null;
	}

	/**
	 * Save new additional data
	 * @param $data
	 * @return integer	id of saved data
	 */
	public function saveData($data)
	{
		global $DIC;
		$db = $DIC->database();

		$id = $db->nextId('pctcp_data');
		$db->insert('pctcp_data',
			array(
				'id' => array('integer', $id),
				'data' => array('text', $data))
		);
		return $id;
	}

	/**
	 * Update additional data
	 * @param integer 	$id
	 * @param string	$data
	 * @return mixed
	 */
	public function updateData($id, $data)
	{
		global $DIC;
		$db = $DIC->database();

		$db->update('pctcp_data',
			array(
				'data' => array('text', $data)),
			array(
				'id' => array('integer', $id))
		);
	}

	/**
	 * Delete additional data
	 * @param $id
	 */
	public function deleteData($id)
	{
		global $DIC;
		$db = $DIC->database();

		$query = "DELETE FROM pctcp_data WHERE ID = " .$db->quote($id, 'integer');
		$db->manipulate($query);
	}
}

?>
