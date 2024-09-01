import { Component, Inject, OnInit, Renderer2 } from '@angular/core';
import {
  animate,
  animateChild,
  group,
  query,
  style,
  transition,
  trigger,
} from '@angular/animations';
import { Subject } from 'rxjs';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import { DOCUMENT } from '@angular/common';
import { Comment } from '../../interfaces/comment';

@Component({
  selector: 'oz-finance-entity-comment-modal',
  templateUrl: './entity-comment-modal.component.html',
  styleUrls: ['./entity-comment-modal.component.scss'],
  animations: [
    trigger('modalContainerAnimation', [
      transition(':enter', [
        group([
          query('@modalBackdropAnimation', animateChild()),
          query('@modalAnimation', animateChild()),
        ]),
      ]),
      transition(':leave', [
        group([
          query('@modalBackdropAnimation', animateChild()),
          query('@modalAnimation', animateChild()),
        ]),
      ]),
    ]),
    trigger('modalBackdropAnimation', [
      transition(':enter', [
        style({ opacity: 0 }),
        animate('300ms ease-in', style({ opacity: 1 })),
      ]),
      transition(':leave', [
        style({ opacity: 1 }),
        animate('200ms ease-out', style({ opacity: 0 })),
      ]),
    ]),
    trigger('modalAnimation', [
      transition(':enter', [
        style({ opacity: 0, transform: 'translateY(1rem)' }),
        animate(
          '300ms ease-in',
          style({ opacity: 1, transform: 'translateY(0)' })
        ),
      ]),
      transition(':leave', [
        style({ opacity: 1, transform: 'translateY(0)' }),
        animate(
          '200ms ease-out',
          style({ opacity: 0, transform: 'translateY(1rem)' })
        ),
      ]),
    ]),
  ],
})
export class EntityCommentModalComponent implements OnInit {
  showCommentModal = false;
  commentForm: FormGroup;

  private modalSubject: Subject<any>;
  public comment: Comment;
  constructor(
    private fb: FormBuilder,
    @Inject(DOCUMENT) private _document,
    private renderer: Renderer2
  ) {}

  ngOnInit(): void {
    //
  }

  submit() {
    if (this.commentForm.valid) {
      this.closeModal(this.commentForm.controls.content.value);
    }
  }

  public openModal(comment?: Comment): Subject<any> {
    this.comment = comment;
    this.initCommentForm();
    this.showCommentModal = true;
    this.modalSubject = new Subject<any>();
    this.renderer.addClass(this._document.body, 'modal-opened');
    return this.modalSubject;
  }

  closeModal(value?: any) {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showCommentModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  dismissModal() {
    this.modalSubject.complete();
    this.showCommentModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  private initCommentForm() {
    this.commentForm = this.fb.group({
      content: new FormControl(this.comment?.content, [
        Validators.min(5),
        Validators.max(3000),
        Validators.required,
      ]),
    });
  }
}
