/* eslint-disabled */
import Contact from "./Contact";
import Company from "./Company";
import MailExtend from "./extends/MailExtend";

export default class Mail extends MailExtend {

public id!: number;
public email = '';
public contact!: Contact;
public company!: Company;
public type? = '';

  constructor (object?: any) {
      super(object)
      if(object){
       this.id= object.id;
       this.email= object.email;
this.contact = (object.contact instanceof Contact) ? object.contact : object.contact ? new Contact(object.contact) : object.contact;
this.company = (object.company instanceof Company) ? object.company : object.company ? new Company(object.company) : object.company;
       this.type= object.type;
      }
      this.postConstruct()
  }

}
