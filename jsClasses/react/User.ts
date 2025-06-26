/* eslint-disabled */
import UserExtend from "./extends/UserExtend";
import { entityManager } from "../services/EntityManager";
import { helper } from "../services/Helper";

export default class User extends UserExtend {


public id!: number;
public uuid = '';
public email = '';
public code = '';
public roles= [];
public godfather? = '';

  constructor (object?: any) {
      super(object)
      if(object){
       this.id= object.id;
       this.uuid= object.uuid;
       this.email= object.email;
       this.code= object.code;
       this.roles= object.roles;
       this.godfather= object.godfather;
          entityManager.persist(this)
      }
      this.postConstruct()
  }

}
