<?php
/**
 * Efd Api Gateway Toolkit
 * @uses Zend
 * @package Toolkit
 * @version 3.17.5
 * @copyright  Copyright (c) 2016
 */

require_once('EfdApiToolkitGateway.php');

class EfdApiToolkit extends EfdApiToolkitGateway
{
	/**
    * Get stocks list
    *
    * @param array $data
    * @return array
    */
	public function getStocksList($filter='',$start='',$count='')
	{
    	return $this->_doRequest('efd.getstockslist',array('filter'=>$filter,'start'=>$start,'count'=>$count));
	}

	/**
    * Add new stock
    *
    * @param array $data
    * @return array
    */
	public function addStock($data='')
	{
    	return $this->_doRequest('efd.addstock',$data);
	}

	/**
    * Get invoices PDF
    *
    * @param int $id
    * @return array
    */
	public function getInvoicePdf($id=0)
	{
	    return $this->_doRequest('efd.getinvoicepdf',array('id'=>$id));
	}

	/**
    * Get invoices PDF
    *
    * @param int $id
    * @return array
    */
	public function getRecurringInvoicePdf($id=0)
	{
	    return $this->_doRequest('efd.getrecurringinvoicepdf',array('id'=>$id));
	}

    /**
    * Validate Iban
    *
    * @param int $iban
    * @return array
    */
    public function validateIban($iban='')
	{
	    return $this->_doRequest('efd.validateiban',array('iban'=>$iban));
	}

    /**
    * update invoice payment
    *
    * @param int $id
    * @return array
    */
	public function updateInvoicePayment($data='')
	{
	    return $this->_doRequest('efd.updateinvoicepayment',$data);
	}

    /**
    * Delete eecurring invoice
    *
    * @param int $id
    * @return array
    */
	public function deleteRecurringInvoice($id=0)
	{
	    return $this->_doRequest('efd.deleterecurringinvoice',array('id'=>$id));
	}

    /**
    * Get the statuses of invoice
    *
    * @param int $id
    * @return array
    */
	public function getStatusOfInvoice($id=0)
	{
	    return $this->_doRequest('efd.getstatusofinvoice',array('id'=>$id));
	}

    /**
    * Get the statuses of invoices
    *
    * @param string $ids
    * @return array
    */
	public function getStatusesOfInvoices($ids='')
	{
	    return $this->_doRequest('efd.getstatusesofinvoices',array('ids'=>$ids));
	}

    /**
    * Get id of contact by login name
    *
    * @param string $login
    * @return array
    */
	public function getContactIdByLogin($login='')
	{
        return $this->_doRequest('efd.getcontactidbylogin',array('login'=>$login));
	}

	/**
    * Validate email format
    *
    * @param array $data
    * @return array
    */
	public function validateEmailFormat($email='',$contact_id=0)
	{
        return $this->_doRequest('efd.validateemailformat',array('email'=>$email,"contact_id"=>$contact_id));
	}

	/**
    * Validate Contact data
    *
    * @param array $data
    * @return array
    */
	public function validateContact($data='')
	{
    	return $this->_doRequest('efd.validatecontact',$data);
	}

	/**
    * Get contact by ID
    *
    * @param array $data
    * @return array
    */
	public function getContactByID($id='')
	{
    	return $this->_doRequest('efd.getcontactbyid',array('id'=>$id));
	}

	/**
    * Get contact ID by contact number
    *
    * @param array $data
    * @return array
    */
	function getContactIdByContactNumber($number='')
	{
		return $this->_doRequest('efd.getcontactidbycontactnumber',array('contact_number'=>$number));
	}

	/**
    * Get invoice by ID
    *
    * @param array $data
    * @return array
    */
	public function getInvoiceByID($id='')
	{
    	return $this->_doRequest('efd.getinvoicebyid',array('id'=>$id));
	}

	/**
    * Get list of products by group id
    *
    * @return array
    */
	public function getProductsListByGroupId($category_id='')
	{
    	return $this->_doRequest('efd.getproductslistbygroupid',array('category_id'=>$category_id));
	}

	/**
    * Get list of reminders
    *
    * @return array
    */
	public function getRemindersList()
	{
    	return $this->_doRequest('efd.getreminderslist');
	}


	/**
    * Get invoices list
    *
    * @param array $filter
    * @param int $start
    * @param int $count
    * @return array
    */
	public function getRepeatingInvoicesList($filter='',$start='',$count='')
	{
    	return $this->_doRequest('efd.getrepeatinginvoiceslist',array('filter'=>$filter,'start'=>$start,'count'=>$count));
	}

	/**
    * Get invoices list
    *
    * @param int $id
    * @return array
    */
	public function getRepeatingInvoicesById($id='')
	{
    	return $this->_doRequest('efd.getrepeatinginvoicesbyid',array('id'=>$id));
	}


	/**
    * Get invoices list
    *
    * @param array $data
    * @return array
    */
	public function getInvoicesList($filter='',$start='',$count='')
	{
    	return $this->_doRequest('efd.getinvoiceslist',array('filter'=>$filter,'start'=>$start,'count'=>$count));
	}

	/**
    * Get payment methods
    *
    * @return array
    */
	public function getPaymentMethods()
	{
	    return $this->_doRequest('efd.getpaymentmethods');
	}

	/**
    * Get customer settings
    *
    * @return array
    */
	public function getCustomerSettings()
	{
	    return $this->_doRequest('efd.getCustomerSettings');
	}

	/**
    * Get unit of mesure list
    *
    * @param array $data
    * @return array
    */
	public function getUomList()
	{
    	return $this->_doRequest('efd.getuomlist');
	}

	/**
    * Get Costcenter list for incoming invoice
    *
    * @param array $data
    * @return array
    */
	public function getCostcenterFull()
	{
    	return $this->_doRequest('efd.getcostcenterfull');
	}

	/**
    * Get Costcenter list for incoming invoice
    *
    * @param array $data
    * @return array
    */
	public function getCostCenterListForIncomingInvoice()
	{
    	return $this->_doRequest('efd.getcostcenterlistforincominginvoice');
	}

	/**
    * Get Costcenter list for invoice
    *
    * @param array $data
    * @return array
    */
	public function getCostCenterListForInvoice()
	{
    	return $this->_doRequest('efd.getcostcenterlistforinvoice');
	}

	/**
    * Get contacts list
    *
    * @param array $data
    * @return array
    */
	public function getContactsList($start='',$count='')
	{
    	return $this->_doRequest('efd.getcontactslist',array('start'=>$start,'count'=>$count));
	}

	/**
    * Get taxes list
    *
    * @return array
    */
	public function getTaxesList()
	{
    	return $this->_doRequest('efd.gettaxeslist');
	}

	/**
    * Get list of products
    *
    * @return array
    */
	public function getProductsList()
	{
    	return $this->_doRequest('efd.getproductslist');
	}

	/**
    * Get list of categories
    *
    * @return array
    */
	public function getCategoriesList()
	{
    	return $this->_doRequest('efd.getcategorieslist');
	}

	/**
    * Get list of products groups
    *
    * @return array
    */
	public function getProductsGroupsList()
	{
    	return $this->_doRequest('efd.getproductsgroupslist');
	}

	/**
    * Get list of documents
    *
    * @return array
    */
	public function getDocumentsList()
	{
    	return $this->_doRequest('efd.getdocumentslist');
	}

	/**
    * Get list of categories
    *
    * @return array
    */
	public function getContactGroupsList()
	{
    	return $this->_doRequest('efd.getcontactgroupslist');
	}

    /**
    * Send invoice
    *
    * @param int $id
    * @return array
    */
	public function sendInvoice($id='',$rem='')
	{
	    return $this->_doRequest('efd.sendinvoice',array('id'=>$id,'rem'=>$rem));
	}

    /**
    * Add new Contact
    *
    * @param array $data
    * @return array
    */
	public function addContact($data='')
	{
	    return $this->_doRequest('efd.addcontact',$data);
	}

    /**
    * Update Contact
    *
    * @param array $data
    * @return array
    */
	public function updateContact($data='')
	{
	    return $this->_doRequest('efd.updatecontact',$data);
	}

	/**
    * Delete contact by ID
    *
    * @param array $id
    * @return array
    */
	public function deleteContact($id='')
	{
    	return $this->_doRequest('efd.deletecontact',array('id'=>$id));
	}

	/**
    * Add new invoice
    *
    * @param array $data
    * @return array
    */
	public function addInvoice($data='')
	{
    	return $this->_doRequest('efd.addinvoice',$data);
	}

    /**
    * Edit recurring invoice
    *
    * @param array $data
    * @return array
    */
	public function editRecurringInvoice($data='')
	{
	    return $this->_doRequest('efd.editrecurringinvoice',$data);
	}

    /**
    * Get info from dashboard
    *
    * @return array
    */
    public function getDashboardData()
	{
    	return $this->_doRequest('efd.getdashboarddata');
	}

    /**
    * Get the status of invoice payment
    *
    * @param int $id
    * @return array
    */
	public function getPayStatusOfInvoice($id=0)
	{
        return $this->_doRequest('efd.getpaystatusofinvoice',array('id'=>$id));
	}

    /**
    * Get info about invoice payment
    *
    * @param int $id
    * @return array
    */
	public function getPaymentInfoOfInvoice($id=0)
	{
	    return $this->_doRequest('efd.getpaymentinfoofinvoice',array('id'=>$id));
	}

    /**
    * Get Payment Url
    *
    * @param int $id
    * @return array
    */
    public function getPaymentUrl($id)
    {
        return $this->_doRequest('efd.getPaymentUrl',array('id'=>$id));
    }

    /**
    * Get Offers list
    *
    * @param int $id
    * @return array
    */
    public function getOfferslist($filter='',$start='',$count='',$status_filter='',$fields='')
    {
    	return $this->_doRequest('efd.getofferslist',array('filter'=>$filter,'start'=>$start,'count'=>$count,'status_filter'=>$status_filter,'fields'=>$fields));
    }

    /**
    * Add new Offer
    *
    * @param array $data
    * @return array
    */
    public function addOffer($data='')
    {
    	return $this->_doRequest('efd.addoffer',$data);
    }

    /**
    * Edit Offer
    *
    * @param array $data
    * @return array
    */
    public function editOffer($data='')
    {
    	return $this->_doRequest('efd.editoffer',$data);
    }

    /**
    * Get Offer by id
    *
    * @param array $id
    * @return array
    */
    public function getOfferByID($id='')
    {
        return $this->_doRequest('efd.getofferbyid',array('id'=>$id));
    }

    /**
    * Delete Offer
    *
    * @param array $id
    * @return array
    */
    public function deleteOffer($id='')
    {
        return $this->_doRequest('efd.deleteoffer',array('id'=>$id));
    }

    /**
    * Edit Invoice
    *
    * @param array $data
    * @return array
    */
    public function editInvoice($data='')
    {
    	return $this->_doRequest('efd.editinvoice',$data);
    }

    /**
    * Send Offer
    *
    * @param int $id
    * @return array
    */
    public function sendOffer($id='')
    {
        return $this->_doRequest('efd.sendoffer',array('id'=>$id));
    }

    /**
    * Get Offer PDF
    *
    * @param int $id
    * @return array
    */
    public function getOfferPdf($id=0)
    {
        return $this->_doRequest('efd.getofferpdf',array('id'=>$id));
    }

    /**
    * Update Offer Status
    *
    * @param int $id
    * @param int $status
    * @param string $text
    * @return array
    */
    public function updateOfferStatus($id=0,$status=0,$text='')
    {
        return $this->_doRequest('efd.updateofferstatus',array('id'=>$id,'status_id'=>$status,'reason_text'=>$text));
    }

    /**
    * Get currencies list
    *
    * @return array
    */
    public function getCurrenciesList()
    {
        return $this->_doRequest('efd.getcurrencies');
    }

    /**
    * Get currencies list
    *
    * @return array
    */
    public function getTemplateProfileList()
    {
        return $this->_doRequest('efd.gettemplateprofilelist');
    }

    /**
    * Add and send invoice
    *
    * @return array
    */
    public function addAndSendInvoice($data='')
    {
        return $this->_doRequest('efd.addandsendinvoice',$data);
    }

    /**
    * Add and send offer
    *
    * @return array
    */
    public function addAndSendOffer($data='')
    {
        return $this->_doRequest('efd.addandsendoffer',$data);
    }

    /**
    * Get list of attached subdomains
    *
    * @return array
    */
    public function getAttachedSubdomains()
    {
        return $this->_doRequest('efd.getattachedsubdomains');
    }

    /**
    * Attach subdomain
    *
    * @return array
    */
    public function attachSubdomain($data='')
    {
        return $this->_doRequest('efd.attachsubdomain',$data);
    }

    /**
    * Detach subdomain
    *
    * @return array
    */
    public function detachSubdomain($id='')
    {
        return $this->_doRequest('efd.detachsubdomain',array('id'=>$id));
    }

    /**
    * Get Change Rates
    *
    * @return array
    */
    public function getChangeRates($date='',$currency='')
    {
        return $this->_doRequest('efd.getchangerates',array('date'=>$date,'currency'=>$currency));
    }

    /**
    * Search Contact
    *
    * @return array
    */
    public function searchContact($filter='',$orand='')
    {
        return $this->_doRequest('efd.searchcontact',array('filter'=>$filter,'orand'=>$orand));
    }

    /**
    * Get Incoming Invoices list
    *
    * @return array
    */
    public function getIncomingInvoicesList($start='',$count='',$status_filter='',$fields='')
    {
    	return $this->_doRequest('efd.getincominginvoiceslist',array('start'=>$start,'count'=>$count,'status_filter'=>$status_filter,'fields'=>$fields));
    }

    /**
    * Get Incoming Invoices list
    *
    * @param int $id
    * @return array
    */
    public function getIncomingInvoiceById($id='')
    {
    	return $this->_doRequest('efd.getincominginvoicebyid',array('id'=>$id));
    }

    /**
    * Get Incoming Invoices attached file
    *
    * @param int $id
    * @return array
    */
    public function getOriginalIncomingInvoice($id='')
    {
    	return $this->_doRequest('efd.getoriginalincominginvoice',array('id'=>$id));
    }

    /**
    * Add Incoming Invoices
    *
    * @return array
    */
    public function addIncomingInvoice($data='')
    {
    	return $this->_doRequest('efd.addincominginvoice',$data);
    }

    /**
    * Edit Incoming Invoices
    *
    * @return array
    */
    public function editIncomingInvoice($data='')
    {
    	return $this->_doRequest('efd.editincominginvoice',$data);
    }

    /**
    * Edit Incoming Invoices
    *
    * @param int $id
    * @return array
    */
    public function deleteIncomingInvoice($id='')
    {
    	return $this->_doRequest('efd.deleteincominginvoice',array('id'=>$id));
    }

    /**
    * Get Payment Info Of Incoming Invoice
    *
    * @param int $id
    * @return array
    */
    public function getPaymentInfoOfIncoming($id='')
    {
    	return $this->_doRequest('efd.getpaymentinfoofincoming',array('id'=>$id));
    }

    /**
    * Get Payment Info Of Incoming Invoice
    *
    * @param int $id
    * @return array
    */
    public function updateIncomingPayment($data='')
    {
    	return $this->_doRequest('efd.updatepaymentforincoming',$data);
    }

    /**
    * Get invoices PDF
    *
    * @param int $id
    * @return array
    */
	public function getInvoicePdfByExternalId($external_id='')
	{
	    return $this->_doRequest('efd.getinvoicepdfbyexternalid',array('external_id'=>$external_id));
	}

    /**
    * Get invoices id by external_id
    *
    * @param int $external_id
    * @return int
    */
    public function getInvoiceIdByExternalId($external_id='')
	{
	    return $this->_doRequest('efd.getinvoiceidbyexternalid',array('external_id'=>$external_id));
	}
     /**
    * Get invoices id by external_id
    *
    * @param int $id
    * @return int
    */
    public function getExternalIdByInvoiceId($id='')
	{
	    return $this->_doRequest('efd.getexternalidbyinvoiceid',array('id'=>$id));
	}
	
	/**
    * Get list of check access 
    *
    * @return array
    */
    public function getCheckAccess()
    {
        return $this->_doRequest('efd.checkaccess');
    }
}
