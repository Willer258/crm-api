/* eslint-disabled */
import LocationExtend from "./extends/LocationExtend";

export default class Location extends LocationExtend {

public id!: number;
public country? = '';
public city? = '';
public region? = '';
public company? = '';
public vpn?: boolean;
public proxy?: boolean;
public tor?: boolean;
public ip = '';
public countryCode? = '';

  constructor (object?: any) {
      super(object)
      if(object){
       this.id= object.id;
       this.country= object.country;
       this.city= object.city;
       this.region= object.region;
       this.company= object.company;
       this.vpn= object.vpn;
       this.proxy= object.proxy;
       this.tor= object.tor;
       this.ip= object.ip;
       this.countryCode= object.countryCode;
      }
      this.postConstruct()
  }

}
