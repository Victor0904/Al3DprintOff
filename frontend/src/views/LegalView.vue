<template>
  <div class="legal-page">
    <div class="container">
      <h1 class="page-title">Mentions Légales</h1>

      <section class="legal-section">
        <h2>Éditeur du site</h2>
        <p><strong>Raison sociale :</strong> Al3DPrint</p>
        <p><strong>Email :</strong> contact@al3dprint.com</p>
        <p><strong>Site web :</strong> https://al3dprint.fr</p>
      </section>

      <section class="legal-section">
        <h2>Hébergement</h2>
        <p>Le site est hébergé par <strong>AlwaysData</strong>, 62 rue Tiquetonne, 75002 Paris, France.</p>
      </section>

      <section class="legal-section">
        <h2>Propriété intellectuelle</h2>
        <p>
          L'ensemble du contenu de ce site (textes, images, graphismes, logo, icônes, sons, logiciels…) est la propriété exclusive
          d'Al3DPrint, à l'exception des marques, logos ou contenus appartenant à d'autres sociétés partenaires ou auteurs.
        </p>
        <p>
          Toute reproduction, représentation, modification, publication, adaptation de tout ou partie des éléments du site, quel que
          soit le moyen ou le procédé utilisé, est interdite, sauf autorisation écrite préalable d'Al3DPrint.
        </p>
      </section>

      <section class="legal-section">
        <h2>Limitation de responsabilité</h2>
        <p>
          Al3DPrint ne pourra être tenu responsable des dommages directs et indirects causés au matériel de l'utilisateur, lors de
          l'accès au site, et résultant soit de l'utilisation d'un matériel ne répondant pas aux spécifications techniques requises,
          soit de l'apparition d'un bug ou d'une incompatibilité.
        </p>
      </section>

      <section class="legal-section">
        <h2>Droit applicable</h2>
        <p>
          Tout litige en relation avec l'utilisation du site est soumis au droit français. Il est fait attribution exclusive de
          juridiction aux tribunaux compétents de Paris.
        </p>
      </section>

      <p class="last-updated">Dernière mise à jour : janvier 2026</p>
    </div>
  </div>
</template>

<script setup>
</script>

<style scoped>
.legal-page {
  min-height: 100vh;
  padding: 60px 0;
}

.page-title {
  font-size: 2.5rem;
  font-weight: bold;
  color: var(--text-primary);
  margin-bottom: 40px;
  background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.legal-section {
  background: rgba(26, 26, 46, 0.6);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 30px;
  margin-bottom: 24px;
}

.legal-section h2 {
  font-size: 1.3rem;
  font-weight: 600;
  color: var(--primary-color);
  margin-bottom: 16px;
}

.legal-section p {
  color: var(--text-secondary);
  line-height: 1.7;
  margin-bottom: 10px;
}

.legal-section p:last-child {
  margin-bottom: 0;
}

.last-updated {
  color: var(--text-secondary);
  font-size: 0.9rem;
  margin-top: 20px;
  font-style: italic;
}
</style>
