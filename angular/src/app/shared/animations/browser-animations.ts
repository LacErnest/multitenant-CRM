import {
  animate,
  animateChild,
  animation,
  group,
  query,
  style,
} from '@angular/animations';

export const displayAnimation = animation([
  style({ opacity: 0 }),
  animate('500ms ease-in', style({ opacity: 1 })),
]);

export const errorEnterMessageAnimation = animation([
  style({ opacity: 0, transform: 'translateY(-100%)' }),
  animate('250ms ease-in', style({ opacity: 1, transform: 'translateY(0)' })),
]);

export const errorLeaveMessageAnimation = animation([
  style({ opacity: 1, transform: 'translateY(0)' }),
  animate(
    '250ms ease-out',
    style({ opacity: 0, transform: 'translateY(-100%)' })
  ),
]);

export const alertEnterAnimation = animation([
  style({ opacity: 0 }),
  animate('500ms ease-in', style({ opacity: 1 })),
]);

export const alertLeaveAnimation = animation([
  style({ opacity: 1 }),
  animate('250ms ease-in', style({ opacity: 0 })),
]);

export const menuEnterAnimation = animation([
  style({ opacity: 0, transform: 'scale(.75)' }),
  animate('250ms ease-out', style({ opacity: 1, transform: 'scale(1)' })),
]);

export const menuLeaveAnimation = animation([
  style({ opacity: 1, transform: 'scale(1)' }),
  animate('250ms ease-in', style({ opacity: 0, transform: 'scale(.75)' })),
]);

export const modalEnterAnimation = animation([
  style({ opacity: 0, transform: 'translateY(1rem)' }),
  animate('300ms ease-in', style({ opacity: 1, transform: 'translateY(0)' })),
]);

export const modalLeaveAnimation = animation([
  style({ opacity: 1, transform: 'translateY(0)' }),
  animate(
    '200ms ease-out',
    style({ opacity: 0, transform: 'translateY(1rem)' })
  ),
]);

export const modalBackdropEnterAnimation = animation([
  style({ opacity: 0 }),
  animate('300ms ease-in', style({ opacity: 1 })),
]);

export const modalBackdropLeaveAnimation = animation([
  style({ opacity: 1 }),
  animate('200ms ease-out', style({ opacity: 0 })),
]);

export const modalContainerEnterAnimation = animation([
  group([
    query('@modalBackdropAnimation', animateChild()),
    query('@modalAnimation', animateChild()),
  ]),
]);

export const modalContainerLeaveAnimation = animation([
  group([
    query('@modalBackdropAnimation', animateChild()),
    query('@modalAnimation', animateChild()),
  ]),
]);
