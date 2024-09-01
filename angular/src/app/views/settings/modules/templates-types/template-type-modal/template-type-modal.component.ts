import {
  animateChild,
  group,
  query,
  transition,
  trigger,
  useAnimation,
} from '@angular/animations';
import { DOCUMENT, getCurrencySymbol } from '@angular/common';
import { Component, Inject, OnInit, Renderer2 } from '@angular/core';
import {
  FormBuilder,
  FormControl,
  FormGroup,
  Validators,
} from '@angular/forms';
import moment from 'moment';
import { Subject } from 'rxjs';
import { EnumService } from 'src/app/core/services/enum.service';
import { GlobalService } from 'src/app/core/services/global.service';
import {
  errorEnterMessageAnimation,
  errorLeaveMessageAnimation,
  modalBackdropEnterAnimation,
  modalBackdropLeaveAnimation,
  modalEnterAnimation,
  modalLeaveAnimation,
} from 'src/app/shared/animations/browser-animations';
import { currencyRegEx } from 'src/app/shared/constants/regex';
import { UserRole } from 'src/app/shared/enums/user-role.enum';
import { Loan } from 'src/app/views/settings/interfaces/loan';
import { environment } from 'src/environments/environment';
import { TemplateModel } from '../../../../../shared/interfaces/template-model';

@Component({
  selector: 'oz-finance-template-type-modal',
  templateUrl: './template-type-modal.component.html',
  styleUrls: ['./template-type-modal.component.scss'],
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
      transition(':enter', useAnimation(modalBackdropEnterAnimation)),
      transition(':leave', useAnimation(modalBackdropLeaveAnimation)),
    ]),
    trigger('modalAnimation', [
      transition(':enter', useAnimation(modalEnterAnimation)),
      transition(':leave', useAnimation(modalLeaveAnimation)),
    ]),
    trigger('errorMessageAnimation', [
      transition(':enter', useAnimation(errorEnterMessageAnimation)),
      transition(':leave', useAnimation(errorLeaveMessageAnimation)),
    ]),
  ],
})
export class TemplateTypeModalComponent implements OnInit {
  public template: TemplateModel;
  public templateForm: FormGroup;
  public showTemplateModal = false;

  private modalSubject: Subject<TemplateModel>;

  constructor(
    @Inject(DOCUMENT) private _document: Document,
    private fb: FormBuilder,
    private globalService: GlobalService,
    private enumService: EnumService,
    private renderer: Renderer2
  ) {}

  public ngOnInit(): void {}

  public openModal(template: TemplateModel): Subject<TemplateModel> {
    this.template = template;
    this.showTemplateModal = true;
    this.initTemplateForm();

    if (this.template) {
      this.templateForm.patchValue(this.template);
    }

    this.modalSubject = new Subject<TemplateModel>();
    this.renderer.addClass(this._document.body, 'modal-opened');
    return this.modalSubject;
  }

  public dismissModal(): void {
    this.modalSubject.complete();
    this.showTemplateModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  public submitTemplateForm(): void {
    if (this.templateForm.valid) {
      const formData: TemplateModel = this.templateForm.getRawValue();

      if (this.template) {
        formData.id = this.template.id;
      }

      this.closeModal(formData);
    }
  }

  private closeModal(value?: TemplateModel): void {
    this.modalSubject.next(value);
    this.modalSubject.complete();
    this.showTemplateModal = false;
    this.renderer.removeClass(this._document.body, 'modal-opened');
  }

  private initTemplateForm(): void {
    this.templateForm = this.fb.group({
      name: new FormControl(undefined, Validators.required),
    });
  }
}
