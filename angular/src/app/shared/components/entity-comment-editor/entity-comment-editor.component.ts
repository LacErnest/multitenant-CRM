import { transition, trigger, useAnimation } from '@angular/animations';
import { CdkDragDrop, moveItemInArray } from '@angular/cdk/drag-drop';
import {
  Component,
  DoCheck,
  EventEmitter,
  Input,
  IterableDiffer,
  IterableDiffers,
  OnChanges,
  OnInit,
  Output,
  SimpleChanges,
  ViewChild,
} from '@angular/core';
import {
  menuEnterAnimation,
  menuLeaveAnimation,
} from 'src/app/shared/animations/browser-animations';
import { EntityItem } from 'src/app/shared/classes/entity-item/entity.item';
import { ConfirmModalComponent } from 'src/app/shared/components/confirm-modal/confirm-modal.component';
import { EntityItemPricePipe } from 'src/app/shared/pipes/entity-item-price.pipe';
import { EntityCommentModalComponent } from '../entity-comment-modal/entity-comment-modal.component';
import { Comment } from '../../interfaces/comment';
import { Invoice } from '../../interfaces/entities';
import { CommentService } from '../../services/comment.service';
import { TablePreferenceType } from '../../enums/table-preference-type.enum';
import { ToastrService } from 'ngx-toastr';
import { finalize } from 'rxjs/operators';
import { ActivatedRoute } from '@angular/router';
import { Subject } from 'rxjs';
import { GlobalService } from '../../../core/services/global.service';
import { User } from 'src/app/core/interfaces/user';
@Component({
  selector: 'oz-finance-entity-comment-editor',
  templateUrl: './entity-comment-editor.component.html',
  styleUrls: ['./entity-comment-editor.component.scss'],
  providers: [EntityItemPricePipe],
  animations: [
    trigger('menuAnimation', [
      transition(':enter', useAnimation(menuEnterAnimation)),
      transition(':leave', useAnimation(menuLeaveAnimation)),
    ]),
  ],
})
export class EntityCommentEditorComponent
  implements OnInit, DoCheck, OnChanges
{
  @Input() public comments: Comment[];
  @Input() public model: Invoice;
  @Input() public entity: number;

  @Input() public readOnly: boolean;
  @Output() public commentAdded = new EventEmitter<Comment>();
  @Output() public commentUpdated = new EventEmitter<Comment>();
  @Output() public commentDeleted = new EventEmitter<number>();
  @Input() modalToogleEvent: Subject<string>;
  @Output() public invoiceUpdated: EventEmitter<Invoice> =
    new EventEmitter<Invoice>();
  @ViewChild('entityCommentModal')
  private entityCommentModal: EntityCommentModalComponent;
  @ViewChild('confirmModal') private confirmModal: ConfirmModalComponent;

  private commentsIterableDiffer: IterableDiffer<any>;
  private modifiersIterableDiffer: IterableDiffer<any>;
  private itemModifiersIterableDiffers: IterableDiffer<any>[] = [];
  public isLoading: boolean;
  public user: User;

  public constructor(
    private entityItemPricePipe: EntityItemPricePipe,
    private commentService: CommentService,
    private iterableDiffers: IterableDiffers,
    private toastrService: ToastrService,
    private route: ActivatedRoute,
    private globalService: GlobalService
  ) {}

  public ngOnInit(): void {
    this.getResolvedData();
    this.readOnly = false;
    this.user = this.globalService.userDetails;
    //const { invoice } = this.route.snapshot.data;
    //this.commentsIterableDiffer = this.iterableDiffers.find(this.comments).create(null);
  }

  public ngOnChanges(changes: SimpleChanges): void {
    //
  }

  public ngDoCheck(): void {
    //
  }

  public itemDropped(event: CdkDragDrop<EntityItem>): void {
    if (event.currentIndex !== event.previousIndex && !this.readOnly) {
      let reorderedItems = JSON.parse(JSON.stringify(this.comments));

      reorderedItems = reorderedItems.map(i => {
        return new EntityItem(i);
      });

      moveItemInArray(reorderedItems, event.previousIndex, event.currentIndex);
      //reorderedItems = EntityItemEditorComponent.assignItemOrders(reorderedItems);
      //this.commentsOrderChanged.emit({ index: event.currentIndex, comments: reorderedItems });
    }
  }

  public addComment(): void {
    this.modalToogleEvent.next('COMMENTS');
    this.entityCommentModal.openModal().subscribe((content: string) => {
      const entity = this.getEntityValue();
      this.isLoading = true;
      this.commentService
        .createComment(entity, this.model.project_id, this.model.id, {
          content,
        })
        .pipe(
          finalize(() => {
            this.isLoading = false;
            this.modalToogleEvent.next(null);
          })
        )
        .subscribe(
          result => {
            this.toastrService.success(
              'Comment created successfully',
              'Success'
            );
            this.comments.unshift(result.comment);
            this.invoiceUpdated.emit(this.model);
          },
          error => {
            this.toastrService.error(
              error?.message ??
                'Something went wrong while trying to create the comment',
              'Error'
            );
          }
        );
    });
  }

  public editComment(index: number, comment: Comment): void {
    this.modalToogleEvent.next('COMMENTS');
    this.entityCommentModal.openModal(comment).subscribe(content => {
      const entity = this.getEntityValue();
      this.isLoading = true;
      this.commentService
        .editComment(entity, this.model.project_id, this.model.id, comment.id, {
          content,
        })
        .pipe(
          finalize(() => {
            this.isLoading = false;
            this.modalToogleEvent.next(null);
          })
        )
        .subscribe(
          result => {
            this.toastrService.success(
              'Comment updated successfully',
              'Success'
            );
            comment.content = result.comment.content;
          },
          error => {
            this.toastrService.error(
              error?.message ??
                'Something went wrong while trying to create the comment',
              'Error'
            );
          }
        );
    });
  }

  public deleteComment(comment): void {
    this.confirmModal
      .openModal('Confirm', 'Are you sure you want to delete this comment?')
      .subscribe(result => {
        if (result) {
          const entity = this.getEntityValue();
          this.isLoading = true;
          this.commentService
            .deleteComment(
              entity,
              this.model.project_id,
              this.model.id,
              comment.id
            )
            .pipe(finalize(() => (this.isLoading = false)))
            .subscribe(
              () => {
                this.toastrService.success(
                  'Comment deleted successfully',
                  'Success'
                );
                this.comments = this.comments.filter(
                  item => item.id !== comment.id
                );
              },
              error => {
                this.toastrService.error(
                  error?.message ??
                    'Something went wrong while trying to create the comment',
                  'Error'
                );
              }
            );
        }
      });
  }

  private getEntityValue(): string {
    return TablePreferenceType[this.entity].toLowerCase();
  }

  private getResolvedData(): void {
    const { comments } = this.route.snapshot.data;
    this.comments = comments;
  }

  refresh(): void {
    this.isLoading = true;
    const entity = this.getEntityValue();
    this.commentService
      .getComments(entity, this.model.project_id, this.model.id)
      .pipe(finalize(() => (this.isLoading = false)))
      .subscribe(response => {
        this.comments = response;
      });
  }
}
