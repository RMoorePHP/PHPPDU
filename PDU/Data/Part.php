<?php

/* 
 * Copyright (C) 2014 jackkum
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'PDU/Data/Header.php';

class PDU_Data_Part {
	
	/**
	 * header message
	 * @var PDU_Data_Header
	 */
	protected $_header;
	
	/**
	 * data in pdu format
	 * @var string
	 */
	protected $_data;
	
	/**
	 * size this part
	 * @var integer
	 */
	protected $_size;
	
	/**
	 * pdu data
	 * @var PDU_Data
	 */
	protected $_parent;
	
	/**
	 * create data part
	 * @param string $data
	 * @param array|NULL $header
	 */
	public function __construct(PDU_Data $parent, $data, $size, $header = NULL)
	{
		// parent 
		$this->_parent = $parent;
		
		// encoded string
		$this->_data = $data;
		
		// size this part
		$this->_size = $size;
		
		// have params for header
		if(is_array($header)){
			// create header
			$this->_header = new PDU_Data_Header($header);
		}
	}
	
	/**
	 * parse pdu string
	 * @param PDU_Data $data
	 * @return array [decded text, text size, self object]
	 * @throws Exception
	 */
	public static function parse(PDU_Data $data)
	{
		$alphabet = $data->getPdu()->getDcs()->getTextAlphabet();
		$header   = NULL;
		$length   = $data->getPdu()->getUdl() * ($alphabet == PDU_DCS::ALPHABET_UCS2 ? 4 : 2);
		
		if($data->getPdu()->getType()->getUdhi()){
			$header = PDU_Data_Header::parse();
		}
		
		$hex = PDU::getPduSubstr($length);
		
		switch($alphabet){
			case PDU_DCS::ALPHABET_DEFAULT:
				PDU::debug("PDU_Helper::decode7bit()");
				$text = PDU_Helper::decode7bit($hex);
				break;
			
			case PDU_DCS::ALPHABET_8BIT:
				PDU::debug("PDU_Helper::decode8bit()");
				$text = PDU_Helper::decode8bit($hex);
				break;
			
			case PDU_DCS::ALPHABET_UCS2:
				PDU::debug("PDU_Helper::decode16Bit()");
				$text = PDU_Helper::decode16Bit($hex);
				break;
			
			default:
				throw new Exception("Unknown alpabet");
		}
		
		$size = mb_strlen($text);
		$self = new self(
			$data, 
			$hex, 
			$size, 
			($header ? $header->toArray() : NULL)
		);
		
		return array(
			$text,
			$size,
			$self
		);
	}


	/**
	 * getter data
	 * @return string
	 */
	public function getData()
	{
		return $this->_data;
	}
	
	/**
	 * getter header
	 * @return PDU_Data_Header
	 */
	public function getHeader()
	{
		return $this->_header;
	}
	
	/**
	 * getter parent of part
	 * @return PDU_Data
	 */
	public function getParent()
	{
		return $this->_parent;
	}
	
	/**
	 * getter size
	 * @return integer
	 */
	public function getSize()
	{
		return $this->_size;
	}
	
	/**
	 * convert pdu to srting
	 * @return string
	 */
	protected function _getPduString()
	{
		return (string) $this->_parent->getPdu();
	}
	
	/**
	 * to hex
	 * @return string
	 */
	protected function _getPartSize()
	{
		return sprintf("%02X", $this->_size);
	}
	
	/**
	 * magic method for cast part to string
	 * @return string
	 */
	public function __toString()
	{
		
		PDU::debug("_getPduString() " . $this->_getPduString());
		PDU::debug("_getPartSize() " . $this->_getPartSize());
		PDU::debug("getHeader() " . $this->getHeader());
		PDU::debug("getData() " . $this->getData());
		
		// concate pdu, size of part, headers, data
		return $this->_getPduString() . 
			   $this->_getPartSize()  . 
			   $this->getHeader()     . 
			   $this->getData();
	}
	
}