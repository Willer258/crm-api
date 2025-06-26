/* eslint-disabled */
import DeviceExtend from "./extends/DeviceExtend";
import { entityManager } from "../services/EntityManager";
import { helper } from "../services/Helper";

export default class Device extends DeviceExtend {


public id!: number;
public mac? = '';
public brand? = '';
public type? = '';
public os? = '';
public browser? = '';
public model? = '';

  constructor (object?: any) {
      super(object)
      if(object){
       this.id= object.id;
       this.mac= object.mac;
       this.brand= object.brand;
       this.type= object.type;
       this.os= object.os;
       this.browser= object.browser;
       this.model= object.model;
          entityManager.persist(this)
      }
      this.postConstruct()
  }

}
