import { Injectable } from '@angular/core';
import {
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { Resource } from 'src/app/shared/interfaces/resource';
import { ResourcesService } from 'src/app/views/resources/resources.service';

@Injectable({
  providedIn: 'root',
})
export class ResourceResolver implements Resolve<Resource> {
  constructor(private resourcesService: ResourcesService) {}

  resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<Resource> | Promise<Resource> | Resource {
    return this.resourcesService.getResource(route.params.resource_id);
  }
}
