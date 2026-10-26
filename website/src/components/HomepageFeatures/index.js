import clsx from 'clsx';
import Heading from '@theme/Heading';
import Translate from '@docusaurus/Translate';
import styles from './styles.module.css';

const FeatureList = [
  {
    id: 'vault',
    title: (
      <Translate id="homepage.features.vault.title">
        Un coffre-fort pour documents sensibles
      </Translate>
    ),
    Svg: require('@site/static/img/undraw_docusaurus_mountain.svg').default,
    description: (
      <Translate id="homepage.features.vault.description">
        Chiffrement d'enveloppe, intégrité vérifiée à chaque consultation,
        effacement RGPD jusqu'à la destruction des clés, journal d'audit,
        clés d'API et rôles.
      </Translate>
    ),
  },
  {
    id: 'clean-architecture',
    title: (
      <Translate id="homepage.features.cleanArchitecture.title">
        Architecture propre et décisions tracées
      </Translate>
    ),
    Svg: require('@site/static/img/undraw_docusaurus_tree.svg').default,
    description: (
      <Translate id="homepage.features.cleanArchitecture.description">
        Clean Architecture et Domain-Driven Design : le domaine métier reste
        indépendant de tout framework. Treize ADR tracent chaque choix.
      </Translate>
    ),
  },
  {
    id: 'changelog',
    title: (
      <Translate id="homepage.features.changelog.title">
        Journal de bord
      </Translate>
    ),
    Svg: require('@site/static/img/undraw_docusaurus_react.svg').default,
    description: (
      <Translate id="homepage.features.changelog.description">
        Refactos, corrections, changements de cap : le blog raconte
        l'évolution du projet au fil des itérations.
      </Translate>
    ),
  },
];

function Feature({Svg, title, description}) {
  return (
    <div className={clsx('col col--4')}>
      <div className="text--center">
        <Svg className={styles.featureSvg} role="img" />
      </div>
      <div className="text--center padding-horiz--md">
        <Heading as="h3">{title}</Heading>
        <p>{description}</p>
      </div>
    </div>
  );
}

export default function HomepageFeatures() {
  return (
    <section className={styles.features}>
      <div className="container">
        <div className="row">
          {FeatureList.map((props) => (
            <Feature key={props.id} {...props} />
          ))}
        </div>
      </div>
    </section>
  );
}
