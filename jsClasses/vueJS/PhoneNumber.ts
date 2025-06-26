/* eslint-disabled */
import Contact from "./Contact";
import Company from "./Company";
import PhoneNumberExtend from "./extends/PhoneNumberExtend";

export default class PhoneNumber extends PhoneNumberExtend {

public contact!: Contact;
public company!: Company;
public id!: number;
public number = '';
public type? = '';

  constructor (object?: any) {
      super(object)
      if(object){
this.contact = (object.contact instanceof Contact) ? object.contact : object.contact ? new Contact(object.contact) : object.contact;
this.company = (object.company instanceof Company) ? object.company : object.company ? new Company(object.company) : object.company;
       this.id= object.id;
       this.number= object.number;
       this.type= object.type;
      }
      this.postConstruct()
  }

}
