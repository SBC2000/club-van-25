<?php

class Member {
	private $id;
	private $year;
	private $firstName;
	private $middleName;
	private $lastName;
	private $email;
	private $hasFile;
	private $token;
	private $deleted;
	
	public function __construct($array) {
		$this->id = $array['id'];
		$this->year = $array['year'];
		$this->firstName = $array['firstname'];
		$this->middleName = $array['middlename'];
		$this->lastName = $array['lastname'];
		$this->email = $array['email'];
		$this->hasFile = $array['hasfile'];
		$this->token = $array['token'];
		$this->deleted = $array['deleted'];
	}
	
	public function toArray() {
		return array(
			'id' => $this->id,
			'year' => $this->year,
			'firstname' => $this->firstName,
			'middlename' => $this->middleName,
			'lastname' => $this->lastName,
			'email' => $this->email,
			'hasfile' => $this->hasFile,
			'token' => $this->token,
			'deleted' => $this->deleted,
		);
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function getYear() {
		return $this->year;
	}

	public function getName() {
		$name = $this->getFirstName();
		$middleName = $this->getMiddleName();
		if ($middleName) {
			$name .= ' ' . $middleName;
		}
		$name .= ' ' . $this->getLastName();
		
		return $name;
	}
	
	public function getFirstName() {
		return $this->firstName;
	}
	
	public function getMiddleName() {
		return $this->middleName;
	}

	public function getLastName() {
		return $this->lastName;
	}
	
	public function getEmail() {
		return $this->email;
	}
	
	public function hasFile() {
		return $this->hasFile;
	}
	
	public function setHasFile($value) {
		$this->hasFile = $value;
	}
	
	public function getFileName() {
		$fileName = "{$this->getYear()} {$this->getName()} [{$this->getId()}]";
		return "accepted/$fileName.jpeg";
	}
	
	public function getToken() {
		return $this->token;
	}
	
	public function setToken($token) {
		$this->token = $token;
	}
	
	public function isDeleted() {
		return $this->deleted;
	}
	
	public function setDeleted($deleted) {
		$this->deleted = $deleted;
	}
}