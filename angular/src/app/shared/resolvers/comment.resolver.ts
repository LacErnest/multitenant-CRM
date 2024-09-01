import { Injectable } from '@angular/core';
import {
  ActivatedRoute,
  ActivatedRouteSnapshot,
  Resolve,
  RouterStateSnapshot,
} from '@angular/router';
import { Observable } from 'rxjs';
import { Comment } from 'src/app/shared/interfaces/comment';
import { CommentService } from 'src/app/shared/services/comment.service';
import { TablePreferenceType } from '../enums/table-preference-type.enum';

@Injectable({
  providedIn: 'root',
})
export class CommentResolver implements Resolve<Comment[]> {
  public constructor(private commentService: CommentService) {}

  public resolve(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<Comment[]> | Promise<Comment[]> | Comment[] {
    const id = route.params.invoice_id || route.params.resource_invoice_id;
    const entity = TablePreferenceType[route.data.entity].toLowerCase();
    return this.commentService.getComments(
      entity,
      route.parent.parent.params.project_id,
      id
    );
  }
}
