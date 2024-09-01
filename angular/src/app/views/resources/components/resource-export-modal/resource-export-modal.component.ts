import {
  Component,
  Inject,
  OnInit,
  Renderer2,
  TemplateRef,
} from '@angular/core';
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

@Component({
  selector: 'oz-finance-resource-export-modal',
  templateUrl: './resource-export-modal.component.html',
  styleUrls: ['./resource-export-modal.component.scss'],
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
export class ResourceExportModalComponent implements OnInit {
  exportForm: FormGroup;
  exportOptions = [
    { key: 'nda', value: 'NDA' },
    { key: 'contractor', value: 'Contractor' },
    { key: 'freelancer', value: 'Freelancer' },
  ];
  showExportModal = false;
  private modalSubject: Subject<any>;

  constructor(
    @Inject(DOCUMENT) private _document,
    private fb: FormBuilder,
    private renderer: Renderer2
  ) {}

  ngOnInit(): void {}

  public openModal(): Subject<any> {
    this.initExportForm();
    this.showExportModal = true;
    this.modalSubject = new Subject<any>();
    this.renderer.addClass(this._document.body, 'modal-opened');
    return this.modalSubject;
  }

  closeModal(value?: any) {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showExportModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  dismissModal() {
    this.modalSubject.complete();
    this.showExportModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  submit() {
    if (this.exportForm.valid) {
      this.modalSubject.next(this.exportForm.getRawValue());
      this.modalSubject.complete();
      this.showExportModal = false;
    }
  }

  private initExportForm() {
    this.exportForm = this.fb.group({
      type: new FormControl('nda', Validators.required),
    });
  }
}
